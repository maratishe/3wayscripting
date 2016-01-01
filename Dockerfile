#
# Building a 3-way scripting environment
# 

# start with the latest PHP release
FROM php:5.6-cli

# add 3-way sctipting files to the raw/base environment
RUN \
	apt-get update && \	
	apt-get -y install wget && \
	apt-get -y install bzip2 && \
	cd / && \
	rm -Rf 3way && \
	mkdir 3way && \
	cd 3way && \
	wget https://github.com/maratishe/3wayscripting/raw/master/allinone.tbz && \
	tar jxvf allinone.tbz 

# set the working directory
EXPOSE 8001
WORKDIR /3way
CMD [ "php example.php server 0.0.0.0:8001"]
