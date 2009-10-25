<?php

define('MAILER_SMTP_HOST', 'localhost');
define('MAILER_SMTP_PORT', 25);
define('MAILER_SMTP_LOGIN', '');
define('MAILER_SMTP_PASSWORD', '');

class Mail {

	protected $smtpHost = MAILER_SMTP_HOST;
	protected $smtpLogin = MAILER_SMTP_LOGIN;
	protected $smtpPassword = MAILER_SMTP_PASSWORD;

	protected $from;
	protected $to;
	protected $cc;
	protected $subject;
	protected $message;

	protected $lastLog;
	protected $lastError;

	function __construct($from, $to, $subject, $message, $cc = array()) {
		$this->from = $from;
		$this->to = is_array($to) ? $to : explode(',', $to);
		$this->cc = is_array($cc) ? $cc : explode(',', $cc);
		$this->subject = $subject;
		$this->message = $message;
	}

	protected function getAddressEmail($address) {
		return preg_match('/<(.+?)>/', $address, $m) ? trim($m[1]) : trim($address);
	}

	protected function getAddressName($address) {
		return preg_match('/^(.*?)</', $address, $m) ? trim($m[1]) : null;
	}

	public function send($test = false) {
		if ($this->to && $this->from) {
			foreach ($this->to as $to) {
				$this->sendBySmtp($this->from, $to, $this->subject, $this->message, $this->cc);
			}
		}
		
		return true;
	}

	protected function sendBySmtp($from, $to, $subject, $message, array $cc = array()) {
		$toName = $this->getAddressName($to);
		$toEmail = $this->getAddressEmail($to);
		$fromName = $this->getAddressName($from);
		$fromEmail = $this->getAddressEmail($from);
		
		$encodedTo = $toName ? "=?utf-8?B?" . base64_encode($toName) . "?= <$toEmail>" : $toEmail;
		$encodedFrom = $fromName ? "=?utf-8?B?" . base64_encode($fromName) . "?= <$fromEmail>" : $fromEmail;
		
		$smtp_conn = fsockopen($this->smtpHost, MAILER_SMTP_PORT, $erno, $errstr, 10);
		
		if (! $smtp_conn) {
			throw new Mail_Exception_Connection("Ошибка соединения с SMTP сервером: $errstr ($erno)");
		}
		
		$this->lastLog = null;
		$this->log('', $this->getSmtpSocketData($smtp_conn));
		
		$OUTs[] = "HELO " . $this->smtpHost . "\r\n";
		
		if ($this->smtpLogin) {
			$OUTs[] = "AUTH LOGIN\r\n";
			$OUTs[] = base64_encode($this->smtpLogin) . "\r\n";
			$OUTs[] = base64_encode($this->smtpPassword) . "\r\n";
		}
		
		$OUTs[] = "MAIL FROM:$fromEmail\r\n";
		$OUTs[] = "RCPT TO:" . $toEmail . "\r\n";
		$OUTs[] = "DATA\r\n";
		
		$header = "Date: " . date("D, j M Y G:i:s O") . "\r\n";
		$header .= "From: " . $encodedFrom . "\r\n";
		$header .= "X-Mailer: YOTA.RU\r\n";
		$header .= "Reply-To: $encodedFrom\r\n";
		$header .= "X-Priority: 3 (Normal)\r\n";
		$header .= "To: $encodedTo\r\n";
		
		if ($cc) {
			foreach ($cc as $c) {
				$cName = $this->getAddressName($c);
				$cEmail = $this->getAddressEmail($c);
				$encodedCC[] = $cName ? "=?utf-8?Q?" . str_replace("+", "_", str_replace("%", "=", urlencode($cName))) . "?= <$cEmail>" : $cEmail;
			}
			$header .= "CC: " . implode(', ', $encodedCC) . "\r\n";
		}
		
		$header .= "Subject: =?utf-8?B?" . base64_encode($subject) . "?=\r\n";
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: text/plain; charset=utf-8\r\n";
		$header .= "Content-Transfer-Encoding: 8bit\r\n";
		
		$OUTs[] = $header . "\r\n" . $message . "\r\n.\r\n";
		
		foreach ($OUTs as $out) {
			fputs($smtp_conn, $out);
			$in = $this->getSmtpSocketData($smtp_conn);
			$this->log($out, $in);
		}
		
		$resultCode = substr($in, 0, 3);
		
		fputs($smtp_conn, "QUIT\r\n");
		$in = $this->getSmtpSocketData($smtp_conn);
		$this->log("QUIT\r\n", $in);
		
		fclose($smtp_conn);
		
		if ($resultCode != 250) {
			throw new Mail_Exception_Sending('Ошибка отправки письма: ' . $this->log());
		}
		
		return true;
	}

	protected function getSmtpSocketData($smtp_conn) {
		$data = '';
		while ($str = fgets($smtp_conn, 515)) {
			$data .= $str;
			if (substr($str, 3, 1) == ' ')
				break;
		}
		return $data;
	}

	public function log($out = null, $in = null) {
		if (! $this->lastLog)
			$this->lastLog = '<table border = "1"><tr><td align="center" width="50%"><b>OUT</b></td><td align="center"><b>IN</b></td></tr>';
		elseif (is_null($out) && is_null($in))
			return $this->lastLog . '</table>';
		else
			$this->lastLog .= '<tr><td><pre>' . (trim($out, "\n\r") ? $out : '&nbsp;') . '</pre></td><td><pre>' . $in . '</pre></td></tr>';
	}
}

class Mail_Exception extends Exception {
}

class Mail_Exception_Connection extends Mail_Exception {
}

class Mail_Exception_Sending extends Mail_Exception {
}