<footer class="footer">
    <div class="footer-top">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-logo" style="text-align:center; margin-bottom:1rem;">
                        <img src="assets/images/logo.png" alt="Rodai Parking Logo" style="height:36px; width:auto; display:inline-block;">
                    </div>
                    <p class="footer-description">
                        Your trusted solution for secure and convenient parking spaces across the city. We make parking hassle-free.
                    </p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-links">
                    <h3 class="footer-title">Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="services.php">Services</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h3 class="footer-title">Services</h3>
                    <ul>
                        <li><a href="#">Monthly Parking</a></li>
                        <li><a href="#">Event Parking</a></li>
                        <li><a href="#">Corporate Accounts</a></li>
                        <li><a href="#">Premium Spots</a></li>
                        <li><a href="#">EV Charging</a></li>
                    </ul>
                </div>
                
                <div class="footer-contact">
                    <h3 class="footer-title">Contact Us</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-text">
                                123 Parking Avenue, Suite 100<br>
                                City, State 12345
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="contact-text">
                                (123) 456-7890
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-text">
                                info@rodaiparking.com
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-text">
                                Mon-Fri: 8:00 AM - 8:00 PM<br>
                                Weekends: 9:00 AM - 5:00 PM
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-content">
                <div class="copyright">
                    &copy; <?php echo date('Y'); ?> Rodai Parking. All rights reserved.
                </div>
                <div class="legal-links">
                    <a href="privacy.php">Privacy Policy</a>
                    <span class="separator">•</span>
                    <a href="terms.php">Terms of Service</a>
                    <span class="separator">•</span>
                    <a href="accessibility.php">Accessibility</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
    .footer {
        background-color: var(--white);
        position: relative;
        margin-top: 6rem;
        border-top: 1px solid rgba(122, 31, 160, 0.1);
    }
    
    .footer:before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(to right, var(--primary), var(--primary-light));
    }
    
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 30px;
    }
    
    .footer-top {
        padding: 70px 0 50px;
    }
    
    .footer-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr 1fr 1.5fr;
        gap: 50px;
    }
    
    .footer-brand {
        padding-right: 30px;
    }
    
    .footer-logo {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        gap: 10px;
    }
    
    .logo-icon {
        width: 36px;
        height: 36px;
        background-color: var(--primary);
        color: var(--white);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: bold;
    }
    
    .logo-text {
        font-size: 18px;
        font-weight: 600;
        color: var(--primary);
        letter-spacing: 0.5px;
    }
    
    .footer-description {
        color: var(--gray-dark);
        line-height: 1.7;
        margin-bottom: 25px;
        font-size: 14px;
    }
    
    .social-links {
        display: flex;
        gap: 12px;
    }
    
    .social-links a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        background-color: var(--light-purple);
        color: var(--primary);
        border-radius: 50%;
        transition: var(--transition);
        text-decoration: none;
        font-size: 16px;
    }
    
    .social-links a:hover {
        background-color: var(--primary);
        color: var(--white);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(122, 31, 160, 0.15);
    }
    
    .footer-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--primary);
        margin-bottom: 20px;
        position: relative;
        padding-bottom: 10px;
    }
    
    .footer-title:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 2px;
        background-color: var(--primary);
    }
    
    .footer-links ul {
        list-style-type: none;
        padding: 0;
        margin: 0;
    }
    
    .footer-links li {
        margin-bottom: 12px;
    }
    
    .footer-links a {
        color: var(--gray-dark);
        text-decoration: none;
        font-size: 14px;
        transition: var(--transition);
        position: relative;
        padding-left: 0;
        display: inline-block;
    }
    
    .footer-links a:before {
        content: "→";
        position: absolute;
        left: -15px;
        opacity: 0;
        transition: var(--transition);
    }
    
    .footer-links a:hover {
        color: var(--primary);
        padding-left: 15px;
    }
    
    .footer-links a:hover:before {
        opacity: 1;
        left: 0;
    }
    
    .contact-info {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .contact-item {
        display: flex;
        align-items: flex-start;
    }
    
    .contact-icon {
        min-width: 38px;
        height: 38px;
        background-color: var(--light-purple);
        color: var(--primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 14px;
        transition: var(--transition);
    }
    
    .contact-item:hover .contact-icon {
        background-color: var(--primary);
        color: var(--white);
        transform: rotate(360deg);
    }
    
    .contact-text {
        font-size: 14px;
        color: var(--gray-dark);
        line-height: 1.6;
    }
    
    .footer-bottom {
        background-color: #fafafa;
        padding: 20px 0;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .footer-bottom-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .copyright {
        font-size: 14px;
        color: var(--gray-dark);
    }
    
    .legal-links {
        display: flex;
        align-items: center;
    }
    
    .legal-links a {
        font-size: 14px;
        color: var(--gray-dark);
        text-decoration: none;
        transition: var(--transition);
    }
    
    .legal-links a:hover {
        color: var(--primary);
    }
    
    .separator {
        margin: 0 10px;
        color: var(--gray);
        font-size: 10px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .footer-grid {
            grid-template-columns: 1fr 1fr;
            gap: 40px 30px;
        }
        
        .footer-brand {
            padding-right: 0;
            grid-column: span 2;
        }
    }
    
    @media (max-width: 768px) {
        .footer-grid {
            grid-template-columns: 1fr;
        }
        
        .footer-brand {
            grid-column: 1;
            text-align: center;
        }
        
        .footer-logo {
            justify-content: center;
        }
        
        .social-links {
            justify-content: center;
        }
        
        .footer-title:after {
            left: 50%;
            transform: translateX(-50%);
        }
        
        .footer-links {
            text-align: center;
        }
        
        .footer-links a:before {
            display: none;
        }
        
        .footer-links a:hover {
            padding-left: 0;
        }
        
        .contact-info {
            align-items: center;
        }
        
        .footer-bottom-content {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
    }
</style>

<!-- Font Awesome -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>