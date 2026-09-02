<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function Email_sender($otp, $email, $subject = 'Your OTP Code') {
    $otp = (string) $otp;
    error_log("Email_sender function called for email: $email");
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    
    $mail = new PHPMailer(true);
    
    // Enable SMTP debug if APP_DEBUG is true
    if (getenv('APP_DEBUG') === 'true') {
        $mail->SMTPDebug = 2; // Enable verbose debug output
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug (level $level): $str");
        };
    }
                    
    try {
        // SMTP settings from environment variables
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USERNAME') ?: 'sunriseyoga007@gmail.com';
        // It is recommended to use an app password for services like Gmail, especially if 2-Factor Authentication is enabled.
        $mail->Password   = getenv('SMTP_PASSWORD') ?: 'qyarmcgmazqycjbz'; // App password
        $mail->SMTPSecure = getenv('SMTP_SECURE') ?: 'tls';
        $mail->Port       = getenv('SMTP_PORT') ?: 587;
        
        error_log("Attempting to send email via {$mail->Host} with username: " . substr($mail->Username, 0, 4) . "***");
        
        // Recipients
        $mail->setFrom(getenv('SMTP_FROM_EMAIL') ?: 'noreply@yogamart.com', getenv('SMTP_FROM_NAME') ?: 'YogaMart');
        $mail->addAddress($email); // user email
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = "<h2>YogaMart - OTP Verification</h2>
                          <p>Your OTP is <b style='font-size: 24px; color: #4a7c59;'>$otp</b></p>
                          <p>This OTP will expire in 5 minutes.</p>
                          <p>If you didn't request this, please ignore this email.</p>
                          <br><p>Best regards,<br>YogaMart Team</p>";
        $mail->AltBody = "YogaMart - Your OTP is $otp. It will expire in 5 minutes.";
        
        $mail->send();
        error_log("OTP email sent successfully to $email with subject: $subject");
        return true;
    } catch (Exception $e) {
        $error_message = "Email could not be sent. PHPMailer Error: " . $e->getMessage() . " | Mailer Error Info: " . $mail->ErrorInfo;
        error_log($error_message);
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['mailer_error'] = $mail->ErrorInfo;
        
        return false;
    }
}

function Contact_Email_sender($name, $email, $phone, $subject, $message, $newsletter) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USERNAME') ?: 'sunriseyoga007@gmail.com';
        $mail->Password   = getenv('SMTP_PASSWORD') ?: 'qyarmcgmazqycjbz';
        $mail->SMTPSecure = getenv('SMTP_SECURE') ?: 'tls';
        $mail->Port       = getenv('SMTP_PORT') ?: 587;
        
        // Recipients
        $mail->setFrom(getenv('SMTP_FROM_EMAIL') ?: 'noreply@yogamart.com', getenv('SMTP_FROM_NAME') ?: 'YogaMart Contact Form');
        $mail->addAddress($mail->Username); // Send TO the business email
        $mail->addReplyTo($email, $name); // Reply to the user who filled the form
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "New Contact Request: " . $subject;
        
        $newsletterStatus = $newsletter ? 'Yes' : 'No';
        
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 10px; padding: 20px;'>
                <h2 style='color: #4a7c59; border-bottom: 2px solid #4a7c59; padding-bottom: 10px;'>New Contact Form Submission</h2>
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Phone:</strong> $phone</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Newsletter Subscription:</strong> $newsletterStatus</p>
                <p><strong>Message:</strong></p>
                <div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #4a7c59; border-radius: 4px; margin-top: 10px; font-style: italic;'>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
                <p style='margin-top: 20px; font-size: 12px; color: #888;'>This email was sent from the Contact Us form on YogaMart.</p>
            </div>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Contact Email Error: " . $e->getMessage());
        return false;
    }
}

?>