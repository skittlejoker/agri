<?php

namespace PHPMailer\PHPMailer;

class PHPMailer
{
    const CHARSET_ASCII = 'us-ascii';
    const CHARSET_ISO88591 = 'iso-8859-1';
    const CHARSET_UTF8 = 'utf-8';
    const CONTENT_TYPE_PLAINTEXT = 'text/plain';
    const CONTENT_TYPE_TEXT_CALENDAR = 'text/calendar';
    const CONTENT_TYPE_TEXT_HTML = 'text/html';
    const CONTENT_TYPE_MULTIPART_ALTERNATIVE = 'multipart/alternative';
    const CONTENT_TYPE_MULTIPART_MIXED = 'multipart/mixed';
    const CONTENT_TYPE_MULTIPART_RELATED = 'multipart/related';
    const ENCODING_7BIT = '7bit';
    const ENCODING_8BIT = '8bit';
    const ENCODING_BASE64 = 'base64';
    const ENCODING_BINARY = 'binary';
    const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';
    const ENCRYPTION_STARTTLS = 'tls';
    const ENCRYPTION_SMTPS = 'ssl';

    public $Priority;
    public $CharSet = self::CHARSET_UTF8;
    public $ContentType = self::CONTENT_TYPE_TEXT_HTML;
    public $Encoding = self::ENCODING_8BIT;
    public $ErrorInfo = '';
    public $From = '';
    public $FromName = '';
    public $Sender = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $SMTPDebug = 0;
    public $SMTPAuth = false;
    public $SMTPSecure = '';
    public $Host = '';
    public $Port = 25;
    public $Username = '';
    public $Password = '';
    public $Mailer = 'smtp';
    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $ReplyTo = [];
    protected $attachments = [];
    protected $exceptions = false;
    protected $isHTML = false;

    public function __construct($exceptions = null)
    {
        if (null !== $exceptions) {
            $this->exceptions = (bool) $exceptions;
        }
    }

    public function setFrom($address, $name = '')
    {
        $this->From = $address;
        $this->FromName = $name;
        return true;
    }

    public function addAddress($address, $name = '')
    {
        $this->to[] = [$address, $name];
        return true;
    }

    public function isSMTP()
    {
        $this->Mailer = 'smtp';
    }

    public function isHTML($isHTML = true)
    {
        $this->isHTML = (bool)$isHTML;
        return $this;
    }

    public function send()
    {
        try {
            if (empty($this->Host)) {
                throw new Exception('SMTP Host not specified');
            }

            // Create SMTP connection
            $smtp = new SMTP();

            if ($this->SMTPDebug) {
                $smtp->do_debug = $this->SMTPDebug;
            }

            // Connect to SMTP server
            if (!$smtp->connect($this->Host, $this->Port)) {
                throw new Exception('SMTP connection failed');
            }

            // Handle authentication if required
            if ($this->SMTPAuth) {
                if (!$smtp->authenticate($this->Username, $this->Password)) {
                    throw new Exception('SMTP authentication failed');
                }
            }

            // Prepare headers
            $headers = "From: {$this->FromName} <{$this->From}>\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: " . ($this->isHTML ? "text/html" : "text/plain") . "; charset={$this->CharSet}\r\n";

            // Send email
            foreach ($this->to as $recipient) {
                list($address, $name) = $recipient;
                if (!$smtp->recipient($address)) {
                    throw new Exception("SMTP recipient failed: $address");
                }
            }

            $message = $this->isHTML ? $this->Body : strip_tags($this->Body);
            if (!$smtp->data($headers . "\r\n" . $message)) {
                throw new Exception('SMTP data failed');
            }

            $smtp->quit();
            return true;
        } catch (Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
    }
}
