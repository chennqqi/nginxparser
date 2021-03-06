<?php

use jorisros\nginxparser\NginxParser;

require_once "jorisros/nginxparser/NginxParser.php";

class NginxparserTest extends PHPUnit_Framework_TestCase
{
    /** @var NginxParser */
    protected $parser;

    protected function setUp()
    {
        //$this->parser = new NginxParser();
    }

    protected function tearDown()
    {
        //unset($this->parser);
    }

    public function testSimple() {
        $expected = <<<EOF

http {
}

EOF;

        $this->parser = new NginxParser('http');
        $actual = $this->parser->build();

        $this->assertEquals($expected, $actual);
    }

    public function testSub() {
        $expected = <<<EOF

server {
	listen		80;
	server_name		localhost local serveralias;
	access_log		/var/log/nginx/log/host.access.log;
	
	location / {
		root		/usr/share/nginx/html;
		index		index.html index.htm;
	}

}

EOF;

        $location = new NginxParser('location','/');
        $location->setRoot('/usr/share/nginx/html')
                 ->setIndex(array('index.html', 'index.htm'));
        $server = new NginxParser('server');
        $server->setListen(80)
            ->setServerName(array('localhost','local','serveralias'))
            ->setAccessLog('/var/log/nginx/log/host.access.log')
            ->setLocation($location);
        $actual = $server->build();

        $this->assertEquals($expected, $actual);
    }

    public function testReadfile() {
        $expected = <<<EOF

http {
	include		/etc/nginx/mime.types;
}

EOF;

        $location = new NginxParser();
        $objs = $location->readFromFile('./tests/nginx_test_comment.conf');

        //$actual = $location->build();
        $actual = reset($objs);

        $this->assertEquals($expected, $actual);
    }

    public function testReadfileComment() {
        $expected = <<<EOF

http {
	include		/etc/nginx/mime.types;
}

EOF;

        $location = new NginxParser();
        $objs = $location->readFromFile('./tests/nginx_test.conf');

        //$actual = $location->build();
        $actual = reset($objs);

        $this->assertEquals($expected, $actual);
    }
}