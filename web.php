<?php
$CLASS = 'web'; class web { // webkey, get, put 
	public $iscli = false;
	public function __construct( $iscli = true) { $this->iscli = $iscli; }
	// webkey for secured access
	public function makewebkey( $libdir = null, $stuffdir = null, $length = 10) { // the key is a substring of [libdir + stuffdir + time] -- stored in webkey.json in libdir
		if ( ! $libdir || ! is_dir( $libdir) || ! $stuffdir || ! is_dir( $stuffdir)) die( " ERROR! webkey make() needs libdir(abspath) stuffdir(abspath) [length=10]\n");
		$h = array(); if ( is_file( "$libdir/webkey.json")) $h = jsonload( "$libdir/webkey.json");
		$md5 = md5( "$libdir  $stuffdir " . tsystem()); $md5p = substr( $md5, 0, $length);
		$h[ $md5p] = $stuffdir; jsondump( $h, "$libdir/webkey.json");
		echo "$md5p   for stuff in $stuffdir -- use code as webkey in GET/POST requests\n";
	}
	public function regwebkey( $key = null, $tag = null, $cldir = null) { // the key is always stored in CLDIR 
		global $CLDIR; if ( ! $cldir) $cldir = $CLDIR;
		if ( ! $key || ! $tag) die( " ERROR! regwebkey() params: key(remote webkey) tag(local)\n");
		$h = array(); if ( is_file( "$cldir/web.json")) $h = jsonload( "$cldir/web.json");
		$h[ $tag] = $key; jsondump( $h, "$cldir/web.json");
		echo "OK, list of keys: " . ltt( hk( $h), '  ') . "\n";
	}
	public function showebkeys( $cldir = null) { 
		global $CLDIR; if ( ! $cldir) $cldir = $CLDIR; $h = is_file( "$cldir/web.json") ? jsonload( "$cldir/web.json") : array(); echo ltt( hk( $h), '  ') . "\n"; 
	}
	// primitive actions
	public function ping( $what) { die( jsonsend( jsonmsg( $what))); } // for checking if this machine is one
	public function run( $where = null, $what = null) { // what is a base64( command) 
		if ( ! $what || ! $where) die( " ERROR! run() params: keytag where(remote dir) what(base64 of command)\n");
		//jsondump( compact( ttl( 'what,where')), '/startup/run.json');
		$cwd = getcwd(); chdir( $where); procpipe( $what); chdir( $cwd); die( jsonsend( jsonmsg( 'ok'))); 
	}
	public function get( $where = null, $what = null) { // returns abs dir pointing to what locally 
		if ( ! $what || ! $where) die( " ERROR! get() params: where(remote dir) what(file in remote dir)\n");
		if ( ! is_file( "$where/$what")) die();
		//jsondump( compact( ttl( 'where,what')), '/startup/get.json');
		die( jsonsendfile( "$where/$what"));
	}
	public function put( $what = null, $where = null, $name = null) { // one=base64( what), two=where, there=name   in POST
		//jsondump( compact( ttl( 'what,where,name')), '/startup/put.json');
		$out = fopen( "$where/$name", 'w'); fwrite( $out, s642s( $what)); fclose( $out); die( jsonsend( jsonmsg( 'ok')));
	}
	public function call( $where = null, $classfunction = null, $one = null, $two = null, $three = null, $four = null) { // classfunction: classname.function
		global $CLDIR, $JO; chdir( $where); extract( lth( ttl( $classfunction, '.'), ttl( 'c,f'))); // c, f
		require_once( "$c.php"); $C = new $c( false); // no output
		$p = 0; foreach ( ttl( 'one,two,three,four') as $k) if ( $$k) $p++;
		if ( ! $p) $JO[ 'status'] = $C->$f();
		if ( $p == 1) $JO[ 'status'] = $C->$f( $one);
		if ( $p == 2) $JO[ 'status'] = $C->$f( $one, $two);
		if ( $p == 3) $JO[ 'status'] = $C->$f( $one, $two, $three);
		if ( $p == 4) $JO[ 'status'] = $C->$f( $one, $two, $three, $four);
		die( jsonsend( jsonmsg( 'OK')));
	}
	public function at( $what, $where = null) { if ( $where) chdir( $where); procat( $what); die( jsonsend( jsonmsg( 'OK'))); }
	// high-level interface
	private function loadkey( $keytag = null, $cldir = null) {
		global $CLDIR; if ( ! $cldir) $cldir = $CLDIR;
		$h = array(); if ( is_file( "$cldir/web.json")) $h = jsonload( "$cldir/web.json");
		if ( ! $keytag || ! isset( $h[ $keytag])) return die( " ERROR! loadkey() no such key#$keytag  at $cldir/web.json\n");
		return $h[ $keytag]; // return the key itself
	}
	public function server( $port = 8002) { $myip = clmyip(); system( "php -S $myip:$port -t ."); } // starts server in current dir 
	public function syncin( $iport = null, $keytag = null, $remotewhere = null, $remotewhat = null, $localwhere = null, $cldir = null) { 
		if ( ! $iport || ! $remotewhere || ! $remotewhat || ! $localwhere) die( " ERROR! syncin()  iport  keytag  remotewhere  remotewhat  localwhere\n");
		$webkey = $this->loadkey( $keytag, $cldir);
		// step 1: run( tar jcvf)
		if ( $this->iscli) echo "step 1: run  "; $file = 'temp.' . tsystem() . '.temp.tbz'; 
		$action = 'run'; $one = $remotewhere; $two = "tar jcvf $file $remotewhat"; $h = compact( ttl( 'webkey,action,one,two')); if ( $this->iscli) echo jsonraw( $h) . "\n";
		if ( $this->iscli) echo "step 1: wget..."; list( $s, $h) = procwget( "http://$iport", $h);
		if ( ! $s || ! $h) { if ( $this->iscli) die( " ERROR ($s/$h=" . jsonraw( compact( ttl( 's,h'))) . ")\n"); return false; }
		if ( $this->iscli) echo " OK\n";
		// step 2: get( file)
		if ( $this->iscli) echo "step 2: get( $remotewhere/$file): "; $cwd = getcwd(); chdir( $localwhere); 
		$action = 'get'; $one = $remotewhere; $two = $file; $h = compact( ttl( 'webkey,action,one,two')); $file2 = procwgetdownload( "http://$iport", $file, $h, true);
		if ( ! $file2 || $file != $file2) { if ( $this->iscli) die( " ERROR! syncin() could not download file2#$file2 = file#$file  in localwhere#$localwhere\n"); return false; }
		if ( $this->iscli) echo " > $localwhere/$file (" . filesize( $file2) . " bytes)\n"; chdir( $cwd);
		// step 3: rm old and untar 
		if ( $this->iscli) echo "step 3: rm old ($localwhere/$remotewhat)..."; $cwd = getcwd(); chdir( $localwhere); procpipe( "rm -Rf $remotewhat"); chdir( $cwd); if ( $this->iscli) echo " OK\n";
		if ( $this->iscli) echo "step 3: untar $file in $localwhere..."; $cwd = getcwd(); chdir( $localwhere); procpipe( "tar jxvf $file"); `rm -Rf $file`; chdir( $cwd); if ( $this->iscli) echo " OK\n";
		// step 4: remove the temp file remotely
		if ( $this->iscli) echo "step 4: run ";  $action = 'run'; $one = $remotewhere; $two = "rm -Rf $file"; $h = compact( ttl( 'webkey,action,one,two')); if ( $this->iscli) echo jsonraw( $h) . "\n";
		if ( $this->iscli) echo "step 4: wget..."; list( $s, $h) = procwget( "http://$iport", $h); if ( $this->iscli) echo " OK\n"; // no need to check the status
		`rm -Rf $localwhere/$file`; return true;
	}
	public function syncout( $iport = null, $keytag = null, $localwhere = null, $localwhat = null, $remotewhere = null, $cldir = null) { 
		if ( ! $iport || ! $localwhere || ! $localwhat || ! $remotewhere) die( " ERROR! syncout() params:  iport  keytag  localwhere  localwhat  remotewhere\n");
		$webkey = $this->loadkey( $keytag, $cldir);
		// step 1: tar
		if ( $this->iscli) echo "step1: local tar ( $localwhat in $localwhere)"; $file = 'temp.' . tsystem() . '.temp.tbz'; if ( $this->iscli) echo " > $file..."; 
		$cwd = getcwd(); chdir( $localwhere); procpipe( "tar jcvf $file $localwhat"); chdir( $cwd); if ( $this->iscli) echo " OK\n";
		// step 2: put
		if ( $this->iscli) echo "step 2: put( $localwhere/$localwhat > $remotewhere)... "; 
		$in = fopen( "$localwhere/$file", 'rb'); $one = s2s64( fread( $in, filesize( "$localwhere/$file"))); fclose( $in); if ( $this->iscli) echo strlen( $one) . " bytes to send\n"; 
		$action = 'put'; $two = $remotewhere; $three = $file; $h = compact( ttl( 'webkey,action,one,two,three')); if ( $this->iscli) echo "step 2: wget " . jsonraw( $h) . '... '; 
		list( $s, $h) = procwpost( "http://$iport", $h);
		if ( ! $s || ! $h) { if ( $this->iscli) die( " ERROR ($s/$h=" . jsonraw( compact( ttl( 's,h'))) . ")\n"); return false; }
		if ( $this->iscli) echo " OK\n"; //`rm -Rf $localwhere/$file`; 
		// step 3: remove old version remotely
		if ( $this->iscli) echo "step 3: run(remote rm) "; $action = 'run'; $one = $remotewhere; $two = "rm -Rf $localwhat"; $h = compact( ttl( 'webkey,action,one,two')); if ( $this->iscli) echo jsonraw( $h) . "\n";
		if ( $this->iscli) echo "step 3: wget..."; list( $s, $h) = procwget( "http://$iport", $h); if ( $this->iscli) echo " OK\n"; // no need to check the status
		// step 4: untar remotely
		if ( $this->iscli) echo "step 4: run(untar at remote $remotewhere/$file)...";  $action = 'run'; $one = $remotewhere; 
		$two = "tar jxvf $file"; $h = compact( ttl( 'webkey,action,one,two')); if ( $this->iscli) echo jsonraw( $h) . "\n";
		if ( $this->iscli) echo "step 4: wget..."; list( $s, $h) = procwget( "http://$iport", $h);
		if ( ! $s || ! $h) { if ( $this->iscli) die( " ERROR ($s/$h=" . jsonraw( compact( ttl( 's,h'))) . ")\n"); return false; } 
		if ( $this->iscli) echo " OK\n";
		// step 5: remove the temp file remotely
		if ( $this->iscli) echo "step 5: run(remote rm) "; $action = 'run'; $one = $remotewhere; $two = "rm -Rf $file"; $h = compact( ttl( 'webkey,action,one,two')); if ( $this->iscli) echo jsonraw( $h) . "\n";
		if ( $this->iscli) echo "step 5: wget..."; list( $s, $h) = procwget( "http://$iport", $h); if ( $this->iscli) echo " OK\n"; // no need to check the status
		`rm -Rf $localwhere/$file`; return array( $s, $h);
	}
	public function rrun( $iport = null, $keytag = null, $remotewhat = null, $remotewhere = null, $cldir = null) { 
		if ( ! $iport || ! $remotewhat) die( " ERROR! rrun() params: iport  remotewhere  remotewhat\n");
		$webkey = $this->loadkey( $keytag, $cldir);
		// step 1: remove old version remotely
		if ( $this->iscli) echo "step 1: run($remotewhat) by at  "; $action = 'at'; $one = urlencode( $remotewhat); 
		if ( $remotewhere) { $two = urlencode( $remotewhere); $h = compact( ttl( 'webkey,action,one,two')); if ( $this->iscli) echo jsonraw( $h) . "\n"; }
		else { $h = compact( ttl( 'webkey,action,one')); if ( $this->iscli) echo jsonraw( $h) . "\n"; }
		if ( $this->iscli) echo "step 1: wget..."; list( $s, $h) = procwget( "http://$iport", $h); if ( $this->iscli) echo " OK" . jsonraw( compact( ttl( 's,h'))) . "\n"; // no need to check the status
		if ( ! $s || ! $h) { if ( $this->iscli) die( " ERROR ($s/$h=" . jsonraw( compact( ttl( 's,h'))) . ")\n"); return false; } 
		return array( $s, $h);
	}
	public function rcall( $iport = null, $keytag = null, $remotewhere = null, $classfunction = null, $params = array(), $cldir = null) { // params: [ param1, param2, ...]
		if ( ! $iport || ! $remotewhere || ! $classfunction) die( " ERROR! rcall() params: iport  keytag remotewhere classfunction params\n");
		$webkey = $this->loadkey( $keytag, $cldir);
		$pnames = ttl( 'three,four,five,six'); if ( is_string( $params)) $params = ttl( $params);
		while ( count( $pnames) > count( $params)) lpop( $pnames); $params = lth( $params, $pnames);
		// step 1: remove old version remotely
		if ( $this->iscli) echo "step 1: call($classfunction at $remotewhere) by at  "; $action = 'call'; $one = $remotewhere; $two = $classfunction; 
		$h = hm( compact( ttl( 'webkey,action,one,two')), $params); foreach ( $h as $k => $v) $h[ $k] = urlencode( $v); if ( $this->iscli) echo jsonraw( $h) . "\n";
		if ( $this->iscli) echo "step 1: wget..."; list( $s, $h) = procwget( "http://$iport", $h); if ( $this->iscli) echo " OK" . jsonraw( compact( ttl( 's,h'))) . "\n"; // no need to check the status
		if ( ! $s || ! $h) { if ( $this->iscli) die( " ERROR ($s/$h=" . jsonraw( compact( ttl( 's,h'))) . ")\n"); return false; } 
		return array( $s, $h);
	}
	public function tellwhenon( $keytag = null, $iport = null, $cldir = null) { 
		if ( ! $keytag || ! $iport) die( " ERROR! tellwhenon()  keytag  iport\n");
		$webkey = $this->loadkey( $keytag, $cldir); $action = 'ping'; $one = 'myping'; $h = compact( ttl( 'webkey,action,one'));
 		$b = tsystem(); $e = echoeinit(); 
 		while ( 1) { list( $s, $h) = procwget( $iport, $h); if ( $this->iscli) echoe( $e, tshinterval( tsystem(), $b) . '  ' . jsonraw( compact( ttl( 's,h')))); if ( $s && $h) break; sleep( 10); }
		if ( $this->iscli) echo " OK($iport is on)\n"; return true;
	}
	
}
// CLI forkjh
if ( isset( $argv) && count( $argv) && strpos( $argv[ 0], "$CLASS.php") !== false) { // direct CLI execution, redirect to one of the functions 
	// this is a standalone script, put the header
	set_time_limit( 0);
	ob_implicit_flush( 1);
	for ( $prefix = is_dir( 'ajaxkit') ? 'ajaxkit/' : ''; ! is_dir( $prefix) && count( explode( '/', $prefix)) < 4; $prefix .= '../'); if ( ! is_file( $prefix . "env.php")) $prefix = '/web/ajaxkit/'; if ( ! is_file( $prefix . "env.php")) die( "\nERROR! Cannot find env.php in [$prefix], check your environment! (maybe you need to go to ajaxkit first?)\n\n");
	if ( is_file( 'requireme.php')) require_once( 'requireme.php'); else foreach ( array( 'functions', 'env') as $k) require_once( $prefix . "$k.php"); clinit(); 
	foreach ( array( 'functions', 'env') as $k) if ( is_file( "$k.php")) require_once( "$k.php");
	chdir( clgetdir()); clparse(); $JSONENCODER = 'jsonencode'; // jsonraw | jsonencode    -- jump to lib dir
	// help
	clhelp( "FORMAT: php$CLASS WDIR COMMAND param1 param2 param3...     ($CLNAME)");
	foreach ( file( $CLNAME) as $line) if ( strpos( trim( $line), 'public function') === 0 && strpos( $line, '__construct') === false) clhelp( trim( str_replace( 'public function', '', $line)));
	// parse command line
	lshift( $argv); if ( ! count( $argv)) die( clshowhelp()); 
	//$wdir = lshift( $argv); if ( ! is_dir( $wdir)) { echo "ERROR! wdir#$wdir is not a directory\n\n"; clshowhelp(); die( ''); }
	//echo "wdir#$wdir\n"; if ( ! count( $argv)) { echo "ERROR! no action after wdir!\n\n"; clshowhelp(); die( ''); }
	$f = lshift( $argv); $C = new $CLASS( true); chdir( $CWD); 
	switch ( count( $argv)) { case 0: $C->$f(); break; case 1: $C->$f( $argv[ 0]); break; case 2: $C->$f( $argv[ 0], $argv[ 1]); break; case 3: $C->$f( $argv[ 0], $argv[ 1], $argv[ 2]); break; case 4: $C->$f( $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3]); break; case 5: $C->$f( $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3], $argv[ 4]); break; case 6: $C->$f( $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3], $argv[ 4], $argv[ 5]); break; }
 	//switch ( count( $argv)) { case 0: $C->$f( $wdir); break; case 1: $C->$f( $wdir, $argv[ 0]); break; case 2: $C->$f( $wdir, $argv[ 0], $argv[ 1]); break; case 3: $C->$f( $wdir, $argv[ 0], $argv[ 1], $argv[ 2]); break; case 4: $C->$f( $wdir, $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3]); break; case 5: $C->$f( $wdir, $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3], $argv[ 4]); break; case 6: $C->$f( $wdir, $argv[ 0], $argv[ 1], $argv[ 2], $argv[ 3], $argv[ 4], $argv[ 5]); break; }
 	die();
}
if ( ! isset( $argv) && ( isset( $_GET) || isset( $_POST)) && ( $_GET || $_POST)) { // web API 
	set_time_limit( 0);
	ob_implicit_flush( 1);
	$prefix = ''; if ( is_dir( "ajaxkit")) $prefix = 'ajaxkit/'; for ( $i = 0; $i < 3; $i++) { if ( ! is_dir( $prefix . 'lib')) $prefix .= '../'; else break; }
	if ( ! is_file( $prefix . "env.php")) $prefix = '/web/ajaxkit/'; // hoping for another location of ajaxkit
	if ( ! is_file( $prefix . "env.php")) die( "\nERROR! Cannot find env.php in [$prefix], check your environment! (maybe you need to go to ajaxkit first?)\n\n");
	// global functions and env
	require_once( $prefix . 'functions.php');
	require_once( $prefix . 'env.php'); //echo "env[" . htt( $env) . "]\n";
	// additional (local) functions and env (if present)
	if ( is_file( "$BDIR/functions.php")) require_once( "$BDIR/functions.php");
	if ( is_file( "$BDIR/env.php")) require_once( "$BDIR/env.php");
	htg( hm( $_GET, $_POST)); 
	// check for webkey.json and webkey parameter in request
	if ( ! is_file( 'webkey.json') || ! isset( $webkey)) die( jsonsend( jsonerr( 'webkey env not set, run [phpweb webkey] first'))); 
	$h = jsonload( 'webkey.json'); if ( ! isset( $h[ "$webkey"])) die( jsonsend( jsonerr( 'no such webkey in your current environment')));
	$wdir = $h[ "$webkey"]; if ( ! is_dir( "$wdir")) die( jsonsend( jsonerr( "no dir $wdir in local filesystem, webkey env is wrong")));
	// actions: [wdir] is fixed/predefined  [action] is function name   others are [one,two,three,...]
	$O = new $CLASS( false, $wdir);  // does not pass [types], expects the user to run init() once locally before using it remotely 
	$p = array(); foreach ( ttl( 'one,two,three,four,five,six') as $k) if ( isset( $$k)) lpush( $p, $$k); $R = array();
	if ( count( $p) == 1) $R = $O->$action( $one);
	if ( count( $p) == 2) $R = $O->$action( $one, $two);
	if ( count( $p) == 3) $R = $O->$action( $one, $two, $three);
	if ( count( $p) == 4) $R = $O->$action( $one, $two, $three, $four);
	if ( count( $p) == 5) $R = $O->$action( $one, $two, $three, $four, $five);
	if ( count( $p) == 6) $R = $O->$action( $one, $two, $three, $four, $five, $six);
	die( jsonsend( $R));
}
if ( isset( $argv) && count( $argv)) { $L = explode( '/', $argv[ 0]); array_pop( $L); if ( count( $L)) chdir( implode( '/', $L)); } // WARNING! Some external callers may not like you jumping to current directory
?>