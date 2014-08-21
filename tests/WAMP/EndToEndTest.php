<?php

class EndToEndTest extends PHPUnit_Framework_TestCase
{

    protected $_conn;
    protected $_error;
    protected $_testArgs;
    protected $_testKWArgs;
    protected $_publicationId;
    protected $_details;
    protected $_testResult;

    public function setUp()
    {
        $this->testArgs = null;
        $this->_testResult = null;
        $this->_error = null;

        $this->_conn = new \Thruway\Connection(
            array(
                "realm" => 'testRealm',
                "url" => 'ws://127.0.0.1:8080',
                "max_retries" => 0,
            )
        );
    }


    public function testCall()
    {
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {
                $session->call('com.example.ping', ['testing123'])->then(
                    function ($res) {
                        $this->_conn->close();
                        $this->_testResult = $res;
                    },
                    function ($error) {
                        $this->_conn->close();
                        $this->_error = $error;
                    }
                );
            }
        );

        $this->_conn->open();

        $this->assertNull($this->_error, "Got this error when making an RPC call: {$this->_error}");
        $this->assertEquals('testing123', $this->_testResult[0]);
    }

    /**
     * @depends testCall
     */
    public function testSubscribe()
    {
        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {

                /**
                 * Subscribe to event
                 */
                $session->subscribe(
                    'com.example.publish',
                    function ($args, $kwargs = null, $details = null, $publicationId = null) {
                        $this->_conn->close();
                        $this->_testArgs = $args;
                        $this->_testKWArgs = $kwargs;
                        $this->_publicationId = $publicationId;

                    }
                );

                /**
                 * Tell the server to publish
                 */
                $session->call('com.example.publish', ['test publish'])->then(
                    function ($res) {
                        $this->_testResult = $res;

                    },
                    function ($error) {
                        $this->_conn->close();
                        $this->_error = $error;
                    }
                );
            }
        );

        $this->_conn->open();

        $this->assertNull($this->_error, "Got this error when making an RPC call: {$this->_error}");
        $this->assertEquals('test publish', $this->_testArgs[0]);
        $this->assertEquals('test1', $this->_testKWArgs['key1']);
        $this->assertNotNull($this->_publicationId);
        $this->assertEquals('ok', $this->_testResult[0]);
    }
}