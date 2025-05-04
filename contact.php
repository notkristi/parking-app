<?php
$success_message = '';
$error_message = '';
$page_title = "Contact Us"; // Set the page title for the header

// Form processing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim(htmlspecialchars($_POST['name'] ?? ''));
    $email = trim(htmlspecialchars($_POST['email'] ?? ''));
    $subject = trim(htmlspecialchars($_POST['subject'] ?? ''));
    $message = trim(htmlspecialchars($_POST['message'] ?? ''));
    
    // Validate form data
    if (empty($name) || empty($email) || empty($message)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Email recipient - change this to your email
        $to = "your-email@example.com";
        
        // Prepare email headers
        $headers = "From: $name <$email>" . "\r\n";
        $headers .= "Reply-To: $email" . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
        
        // Email body
        $email_body = "<p><strong>Name:</strong> $name</p>";
        $email_body .= "<p><strong>Email:</strong> $email</p>";
        $email_body .= "<p><strong>Subject:</strong> $subject</p>";
        $email_body .= "<p><strong>Message:</strong></p><p>" . nl2br($message) . "</p>";
        
        // Send email
        if (mail($to, "Contact Form: $subject", $email_body, $headers)) {
            $success_message = "Thank you for your message! We'll get back to you soon.";
            // Clear form fields after successful submission
            $name = $email = $subject = $message = '';
        } else {
            $error_message = "Sorry, there was an error sending your message. Please try again.";
        }
    }
}

// Include header
include 'includes/header.php';
?>

<style>
    /* Contact page specific styles */
    .contact-section {
        position: relative;
        z-index: 1;
        padding: 50px 0;
    }
    
    /* Decorative elements */
    .contact-section:before {
        content: "";
        position: absolute;
        top: -30px;
        left: -30px;
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background-color: rgba(142, 68, 173, 0.05);
        z-index: -1;
    }
    
    .contact-section:after {
        content: "";
        position: absolute;
        bottom: -50px;
        right: -50px;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background-color: rgba(142, 68, 173, 0.07);
        z-index: -1;
    }
    
    .contact-container {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 50px;
        margin-bottom: 60px;
        position: relative;
        max-width: 1200px;
        margin: 0 auto 60px;
        padding: 0 20px;
    }
    
    .page-header {
        text-align: center;
        margin-bottom: 60px;
        position: relative;
        max-width: 1200px;
        margin: 0 auto 60px;
        padding: 0 20px;
    }
    
    .page-header:after {
        content: "";
        display: block;
        width: 80px;
        height: 4px;
        background: var(--primary-light);
        margin: 15px auto 0;
        border-radius: 2px;
    }
    
    .page-header h1 {
        font-size: 2.8rem;
        margin-bottom: 15px;
        color: var(--primary-color);
        font-weight: 600;
    }
    
    .subtitle {
        font-size: 1.2rem;
        color: var(--light-text);
        max-width: 700px;
        margin: 0 auto;
    }
    
    .contact-info-card {
        background: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 40px;
        position: relative;
        overflow: hidden;
        height: fit-content;
    }
    
    /* Purple accent for contact card */
    .contact-info-card:before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 10px;
        background: linear-gradient(to right, var(--primary-color), var(--primary-light));
    }
    
    .contact-info-header {
        margin-bottom: 35px;
        position: relative;
    }
    
    .contact-info-header h2 {
        font-size: 1.8rem;
        color: var(--primary-color);
        margin-bottom: 15px;
        font-weight: 600;
    }
    
    .contact-info-header p {
        color: var(--light-text);
        font-size: 1rem;
    }
    
    .contact-info-list {
        list-style: none;
        margin-bottom: 40px;
    }
    
    .contact-info-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 30px;
    }
    
    .contact-info-item:last-child {
        margin-bottom: 0;
    }
    
    .contact-icon-wrapper {
        min-width: 50px;
        height: 50px;
        background: var(--secondary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin-right: 20px;
        transition: var(--transition);
    }
    
    .contact-icon-wrapper i {
        font-size: 20px;
        color: var(--primary-color);
    }
    
    .contact-info-item:hover .contact-icon-wrapper {
        background: var(--primary-color);
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(142, 68, 173, 0.3);
    }
    
    .contact-info-item:hover .contact-icon-wrapper i {
        color: var(--white);
    }
    
    .contact-info-content h3 {
        font-size: 1.1rem;
        color: var(--primary-color);
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .contact-info-content p {
        color: var(--text-color);
        font-size: 1rem;
        line-height: 1.5;
    }
    
    .social-links {
        margin-top: 40px;
        padding-top: 30px;
        border-top: 1px dashed rgba(142, 68, 173, 0.2);
    }
    
    .social-links h3 {
        font-size: 1.1rem;
        color: var(--primary-color);
        margin-bottom: 20px;
    }
    
    .social-icons {
        display: flex;
        gap: 15px;
    }
    
    .social-icon-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background-color: var(--secondary-color);
        border-radius: 50%;
        color: var(--primary-color);
        font-size: 18px;
        transition: var(--transition);
        text-decoration: none;
    }
    
    .social-icon-link:hover {
        background-color: var(--primary-color);
        color: var(--white);
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(142, 68, 173, 0.3);
    }
    
    .contact-form-wrapper {
        background: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 50px;
        position: relative;
        overflow: hidden;
    }
    
    .form-header {
        margin-bottom: 35px;
    }
    
    .form-header h2 {
        font-size: 1.8rem;
        color: var(--primary-color);
        margin-bottom: 15px;
        font-weight: 600;
    }
    
    .form-header p {
        color: var(--light-text);
    }
    
    .alert {
        padding: 15px;
        border-radius: var(--border-radius);
        margin-bottom: 25px;
        font-weight: 500;
    }
    
    .alert-success {
        background-color: rgba(39, 174, 96, 0.15);
        color: var(--success);
        border-left: 4px solid var(--success);
    }
    
    .alert-error {
        background-color: rgba(231, 76, 60, 0.15);
        color: var(--error);
        border-left: 4px solid var(--error);
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--text-color);
        font-size: 0.95rem;
    }
    
    .required {
        color: var(--error);
    }
    
    input, textarea, select {
        width: 100%;
        padding: 15px;
        background-color: #f9f4ff;
        border: 1px solid rgba(142, 68, 173, 0.2);
        border-radius: var(--border-radius);
        font-size: 1rem;
        color: var(--text-color);
        font-family: inherit;
        transition: var(--transition);
    }
    
    input:focus, textarea:focus, select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(142, 68, 173, 0.2);
        background-color: var(--white);
    }
    
    textarea {
        min-height: 150px;
        resize: vertical;
    }
    
    .btn-submit {
        display: inline-block;
        background: linear-gradient(to right, #7A1FA0, #9b59b6);
        color: #fff;
        padding: 15px 35px;
        border: none;
        border-radius: 30px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 16px rgba(122, 31, 160, 0.18);
        margin-top: 10px;
        outline: none;
    }
    
    .btn-submit:hover, .btn-submit:focus {
        background: linear-gradient(to right, #5E1681, #7A1FA0);
        color: #fff;
        box-shadow: 0 8px 24px rgba(122, 31, 160, 0.25);
        transform: translateY(-2px) scale(1.03);
    }
    
    .map-section {
        margin-top: 80px;
        max-width: 1200px;
        margin: 80px auto 0;
        padding: 0 20px;
    }
    
    .map-container {
        height: 400px;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--box-shadow);
        position: relative;
    }
    
    .map-container iframe {
        width: 100%;
        height: 100%;
        border: 0;
    }
    
    /* Decorative floating shapes */
    .shape {
        position: absolute;
        opacity: 0.5;
        z-index: -1;
    }
    
    .shape-1 {
        top: 10%;
        left: 5%;
        width: 80px;
        height: 80px;
        background-color: rgba(142, 68, 173, 0.1);
        border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
        animation: float 6s ease-in-out infinite;
    }
    
    .shape-2 {
        bottom: 20%;
        right: 10%;
        width: 120px;
        height: 120px;
        background-color: rgba(142, 68, 173, 0.1);
        border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
        animation: float 8s ease-in-out infinite;
    }
    
    @keyframes float {
        0% {
            transform: translateY(0px) rotate(0deg);
        }
        50% {
            transform: translateY(-20px) rotate(5deg);
        }
        100% {
            transform: translateY(0px) rotate(0deg);
        }
    }
    
    @media (max-width: 992px) {
        .contact-container {
            grid-template-columns: 1fr;
        }
        
        .contact-info-card {
            order: 2;
        }
        
        .contact-form-wrapper {
            order: 1;
        }
        
        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }
    }
    
    @media (max-width: 768px) {
        .page-header h1 {
            font-size: 2.2rem;
        }
        
        .contact-form-wrapper,
        .contact-info-card {
            padding: 30px;
        }
        
        .form-header h2,
        .contact-info-header h2 {
            font-size: 1.5rem;
        }
    }
</style>

<div class="logo" style="text-align:center; margin-bottom:1.5rem;">
    <img src="assets/images/logo.png" alt="Rodai Parking Logo" style="height:48px; width:auto; display:inline-block;">
</div>

<div class="page-header">
    <h1>Contact Us</h1>
    <p class="subtitle">We'd love to hear from you! Please fill out the form below or use our contact information to get in touch.</p>
</div>

<div class="contact-section">
    <!-- Decorative elements -->
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    
    <div class="contact-container">
        <div class="contact-info-card">
            <div class="contact-info-header">
                <h2>Get In Touch</h2>
                <p>We're here to help and answer any questions you might have.</p>
            </div>
            
            <ul class="contact-info-list">
                <li class="contact-info-item">
                    <div class="contact-icon-wrapper">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-info-content">
                        <h3>Our Location</h3>
                        <p>123 Lavender Lane, Suite 100<br>Violet Valley, NY 10001</p>
                    </div>
                </li>
                
                <li class="contact-info-item">
                    <div class="contact-icon-wrapper">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="contact-info-content">
                        <h3>Phone Number</h3>
                        <p>+1 (555) 123-4567</p>
                    </div>
                </li>
                
                <li class="contact-info-item">
                    <div class="contact-icon-wrapper">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-info-content">
                        <h3>Email Address</h3>
                        <p>hello@purplecompany.com</p>
                    </div>
                </li>
                
                <li class="contact-info-item">
                    <div class="contact-icon-wrapper">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="contact-info-content">
                        <h3>Business Hours</h3>
                        <p>Monday - Friday: 9:00 AM - 5:00 PM<br>Saturday - Sunday: Closed</p>
                    </div>
                </li>
            </ul>
            
            <div class="social-links">
                <h3>Connect With Us</h3>
                <div class="social-icons">
                    <a href="#" class="social-icon-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon-link"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-icon-link"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
        </div>
        
        <div class="contact-form-wrapper">
            <div class="form-header">
                <h2>Send Us a Message</h2>
                <p>Have questions or feedback? We'd love to hear from you.</p>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" value="<?php echo $name ?? ''; ?>" placeholder="Your name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo $email ?? ''; ?>" placeholder="your.email@example.com" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" value="<?php echo $subject ?? ''; ?>" placeholder="What is this regarding?">
                </div>
                
                <div class="form-group">
                    <label for="message">Message <span class="required">*</span></label>
                    <textarea id="message" name="message" placeholder="Type your message here..." required><?php echo $message ?? ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn-submit">Send Message</button>
            </form>
        </div>
    </div>
    
    <div class="map-section">
        <div class="map-container">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.215151277125!2d-73.98784532392361!3d40.75850657138591!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25855c6480299%3A0x55194ec5a1ae072e!2sTimes%20Square!5e0!3m2!1sen!2sus!4v1693504146355!5m2!1sen!2sus" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>