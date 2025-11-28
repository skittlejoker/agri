<?php

namespace PHPMailer\PHPMailer;

class SMTP
{
    const VERSION = '6.9.1';
    const CRLF = "\r\n";
    const DEFAULT_SMTP_PORT = 25;
    const MAX_LINE_LENGTH = 998;
    const DEBUG_OFF = 0;
    const DEBUG_CLIENT = 1;
    const DEBUG_SERVER = 2;
    const DEBUG_CONNECTION = 3;
    const DEBUG_LOWLEVEL = 4;

    public $do_debug = self::DEBUG_OFF;
    public $Debugoutput = 'echo';
    public $do_verp = false;
    protected $smtp_conn;
    protected $error = [];
    protected $helo_rply;
    protected $server_caps;
    protected $last_reply = '';

    public function connect($host, $port = null, $timeout = 30, $options = [])
    {
        // Clear errors to avoid confusion
        $this->error = [];

        if ($this->connected()) {
            $this->error = ['error' => 'Already connected to a server'];
            return false;
        }

        if (empty($port)) {
            $port = self::DEFAULT_SMTP_PORT;
        }

        $this->smtp_conn = @fsockopen(
            $host,
            $port,
            $errno,
            $errstr,
            $timeout
        );

        if (empty($this->smtp_conn)) {
            $this->error = [
                'error' => 'Failed to connect to server',
                'errno' => $errno,
                'errstr' => $errstr
            ];
            return false;
        }

        return true;
    }

    public function authenticate($username, $password)
    {
        return true; // Simplified for this example
    }

    public function recipient($address)
    {
        return true; // Simplified for this example
    }

    public function data($msg_data)
    {
        return true; // Simplified for this example
    }

    public function quit()
    {
        $this->error = [];
        if (!$this->connected()) {
            return false;
        }
        if (fclose($this->smtp_conn)) {
            $this->smtp_conn = null;
            return true;
        }
        return false;
    }

    protected function connected()
    {
        if (!empty($this->smtp_conn)) {
            $status = stream_get_meta_data($this->smtp_conn);
            return !empty($status['eof']);
        }
        return false;
    }
}
