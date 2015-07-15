
This project introduces 3-way scripts/APIs/intrafaces, where the 3 ways are:

 - command line interface 
 - library, where the script is imported as class/object/instance
 - web API, where the script is called over the network

The idea itself is abstract and can be implemented in many languages.  This particular implementation is in PHP.   PHP has a unique approach to calling- and passing-   by reference, which makes for a very short implementation of the **3-way scripts**.  Implementation in other langauges may require more work, but *Python*, *Ruby* and even *Node.js* should work out about the same. 

If it was not obvious from the description above, **3-way scripts** are a new way to look at distributed software.  In cloud environments, traditional distribution platforms are too bulky to both install and operate, while the **3-way scripts** offer near-zero installation and maintenance overhead and thus are much for suitable for clouds. 

## On the Web API mode

Web APIs need a **web server** to get requests.  In PHP, the standalone server is simply run as `php -S ip:port -t .`  for the case of running a server in the local directory.  Although it is not advised to use that setup for commercial purposes, it works fine for research and back-end processing in clouds.  I have never had that **mini web server** fail on me. 


## On Secure Use of 3-Way Scripts

In **Web API** mode, It is sometimes necessary to make sure that whoever users the API over the network is authorized to do so.   This cannot protect against DoS or DDoS, but it will not let just anyone use the API.  `web.php` shipped with this code is a 3-way script that does just that.  Never mind that it itself is a 3-way script -- I write most of the scripts in this design resently because most of my code runs in the cloud.  You should probably not use `web.php` as a Web API, but this is easy to control -- simply do not run a mini web server for this script. 

Run `php web.php makewebkey LIDIR STUFFDIR` to register a new key, where `LIBDIR` is the directory for the 3-way script you need to secure and `STUFFDIR` is the directory the 3-way script should jump before processing your request.  The second parameter is useful when your 3-way scripts have to move to other folder to process data. 

Note that you need to un-comment-out the **webkey** part in the Web API mode of your 3-way script.  It is commented out by default, leaving your scripts wide open.

That's it. 

