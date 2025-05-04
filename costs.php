<?php
include 'db.php'; // Database connection

// Set page title for header
$page_title = "Parking Rates & Costs";

// Include header
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Parking Rates & Plans</h1>
            <p class="page-subtitle">Find flexible pricing options to meet your parking needs, whether you're stopping by for a few hours or staying long-term.</p>
        </div>
        
        <!-- Current Rates Section -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">Current Pricing</h2>
                <p class="section-description">Our active parking rate plans. All rates are effective within the displayed date ranges.</p>
            </div>
            
        <?php
        try {
            $stmt = $conn->prepare("SELECT * FROM pricingrates WHERE IsActive = 1 ORDER BY EffectiveFrom DESC");
            $stmt->execute();
            $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($rates) > 0): ?>
                    <div class="pricing-cards">
                    <?php foreach ($rates as $rate): ?>
                            <div class="pricing-card">
                                <div class="pricing-badge">Active</div>
                                <div class="pricing-header">
                                    <h3 class="pricing-title"><?php echo htmlspecialchars($rate['RateName']); ?></h3>
                                </div>
                                <div class="pricing-body">
                                    <ul class="pricing-features">
                                        <li>
                                            <div class="feature-icon"><i class="fas fa-clock"></i></div>
                                            <div class="feature-content">
                                                <span class="feature-label">Hourly</span>
                                                <span class="feature-value">$<?php echo number_format($rate['HourlyRate'], 2); ?></span>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="feature-icon"><i class="fas fa-calendar-day"></i></div>
                                            <div class="feature-content">
                                                <span class="feature-label">Daily</span>
                                                <span class="feature-value">$<?php echo number_format($rate['DailyRate'], 2); ?></span>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="feature-icon"><i class="fas fa-calendar-week"></i></div>
                                            <div class="feature-content">
                                                <span class="feature-label">Weekly</span>
                                                <span class="feature-value">$<?php echo number_format($rate['WeeklyRate'], 2); ?></span>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="feature-icon"><i class="fas fa-calendar-alt"></i></div>
                                            <div class="feature-content">
                                                <span class="feature-label">Monthly</span>
                                                <span class="feature-value">$<?php echo number_format($rate['MonthlyRate'], 2); ?></span>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="feature-icon"><i class="fas fa-star"></i></div>
                                            <div class="feature-content">
                                                <span class="feature-label">Special Rate</span>
                                                <span class="feature-value">$<?php echo number_format($rate['SpecialRate'], 2); ?></span>
                            </div>
                                        </li>
                            </ul>
                                    
                                    <div class="pricing-date">
                                <i class="fas fa-calendar-check"></i>
                                <span>Effective: <?php echo date('M d, Y', strtotime($rate['EffectiveFrom'])); ?> - <?php echo date('M d, Y', strtotime($rate['EffectiveTo'])); ?></span>
                            </div>
                                </div>
                                <div class="pricing-footer">
                                    <button class="btn-select" onclick="selectRate('<?php echo htmlspecialchars($rate['RateName']); ?>')">
                                Select This Plan
                            </button>
                                </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-info-circle"></i></div>
                        <p>No active parking plans at the moment. Please check back soon!</p>
                </div>
            <?php endif;
        } catch (PDOException $e) {
                echo "<div class='error-message'><i class='fas fa-exclamation-circle'></i> Error loading rates: " . $e->getMessage() . "</div>";
            }
            ?>
        </section>
        
        <!-- Special Offers Section -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">Special Offers</h2>
                <p class="section-description">Take advantage of our special promotions and discounts.</p>
            </div>
            
            <div class="offers-grid">
                <div class="offer-card">
                    <div class="offer-icon"><i class="fas fa-graduation-cap"></i></div>
                    <h3 class="offer-title">Student Discount</h3>
                    <p class="offer-description">Show your valid student ID and receive 10% off any hourly or daily rate.</p>
                </div>
                
                <div class="offer-card">
                    <div class="offer-icon"><i class="fas fa-users"></i></div>
                    <h3 class="offer-title">Group Rates</h3>
                    <p class="offer-description">Booking for 5+ vehicles? Contact us for special group rates and reserved sections.</p>
                </div>
                
                <div class="offer-card">
                    <div class="offer-icon"><i class="fas fa-calendar-check"></i></div>
                    <h3 class="offer-title">Early Bird Special</h3>
                    <p class="offer-description">Arrive before 9 AM and pay a flat rate of $12 for the entire day (exit by 6 PM).</p>
                </div>
                
                <div class="offer-card">
                    <div class="offer-icon"><i class="fas fa-moon"></i></div>
                    <h3 class="offer-title">Overnight Special</h3>
                    <p class="offer-description">Park from 6 PM to 6 AM for our special overnight rate of just $15.</p>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">Frequently Asked Questions</h2>
                <p class="section-description">Find answers to common questions about our parking rates and payment options.</p>
            </div>
            
            <div class="faq-container">
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h3>What payment methods do you accept?</h3>
                        <div class="faq-icon"><i class="fas fa-chevron-down"></i></div>
                    </div>
                    <div class="faq-answer">
                        <p>We accept all major credit cards, mobile payment apps, and cash. Monthly subscriptions can be set up with automatic billing.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h3>Are there any hidden fees?</h3>
                        <div class="faq-icon"><i class="fas fa-chevron-down"></i></div>
                    </div>
                    <div class="faq-answer">
                        <p>All rates are inclusive of VAT and local taxes. There are no hidden fees. Any additional charges will be clearly displayed before payment.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h3>What happens if I stay longer than my paid time?</h3>
                        <div class="faq-icon"><i class="fas fa-chevron-down"></i></div>
                        </div>
                    <div class="faq-answer">
                        <p>We provide a 15-minute grace period. After that, you'll be charged for the next time increment at the current rate.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h3>Do you offer refunds for unused time?</h3>
                        <div class="faq-icon"><i class="fas fa-chevron-down"></i></div>
                        </div>
                    <div class="faq-answer">
                        <p>We do not offer refunds for unused parking time. The rate you pay reserves your spot for the entire duration selected.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h3>Can I extend my parking time remotely?</h3>
                        <div class="faq-icon"><i class="fas fa-chevron-down"></i></div>
                        </div>
                    <div class="faq-answer">
                        <p>Yes! Through our mobile app or website, you can extend your parking time remotely without returning to your vehicle.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Call to Action -->
        <section class="cta-section">
            <div class="cta-content">
                <h2>Ready to Reserve Your Spot?</h2>
                <p>Book your parking space now and enjoy hassle-free parking at competitive rates.</p>
                <a href="reserve.php" class="cta-button">Reserve Now</a>
            </div>
        </section>
    </div>
</div>

<style>
    :root {
        --primary: #7A1FA0;
        --primary-light: #9b59b6;
        --primary-dark: #5E1681;
        --secondary: #8e44ad;
        --accent: #7d3c98;
        --success: #27ae60;
        --warning: #f39c12;
        --danger: #e74c3c;
        --light: #f5f5f5;
        --light-purple: #F5EAFA;
        --dark: #333333;
        --gray: #95a5a6;
        --gray-light: #ecf0f1;
        --gray-dark: #7f8c8d;
        --black: #1a1a1a;
        --white: #ffffff;
        --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        --transition: all 0.3s ease;
        --border-radius: 8px;
    }
    
    .main-content {
        padding: 120px 0 80px;
        background-color: #f9f9f9;
        min-height: 100vh;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    /* Page Header */
    .page-header {
        text-align: center;
        margin-bottom: 60px;
    }
    
    .page-title {
        font-size: 42px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 15px;
        font-family: 'Playfair Display', serif;
    }
    
    .page-subtitle {
        font-size: 18px;
        color: var(--gray-dark);
        max-width: 700px;
        margin: 0 auto;
        line-height: 1.6;
    }
    
    /* Section Styling */
    .section {
        margin-bottom: 80px;
    }
    
    .section-header {
        text-align: center;
        margin-bottom: 40px;
        position: relative;
    }
    
    .section-title {
        font-size: 28px;
        font-weight: 600;
        color: var(--primary-dark);
        margin-bottom: 15px;
        position: relative;
        display: inline-block;
    }
    
    .section-title::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 70px;
        height: 3px;
        background: linear-gradient(to right, var(--primary-dark), var(--primary-light));
        border-radius: 3px;
    }
    
    .section-description {
        font-size: 16px;
        color: var(--gray-dark);
        max-width: 700px;
        margin: 0 auto;
    }
    
    /* Pricing Cards */
    .pricing-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 30px;
        margin-top: 30px;
    }
    
    .pricing-card {
        background-color: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        overflow: hidden;
        position: relative;
        transition: var(--transition);
        border: 1px solid var(--gray-light);
    }
    
    .pricing-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }
    
    .pricing-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background-color: var(--primary);
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 3px 10px rgba(122, 31, 160, 0.2);
    }
    
    .pricing-header {
        padding: 25px 30px;
        background-color: var(--light-purple);
        border-bottom: 1px solid var(--gray-light);
    }
    
    .pricing-title {
        font-size: 22px;
        font-weight: 600;
        color: var(--primary);
        margin: 0;
    }
    
    .pricing-body {
        padding: 30px;
    }
    
    .pricing-features {
        list-style: none;
        padding: 0;
        margin: 0 0 25px 0;
    }
    
    .pricing-features li {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .pricing-features li:last-child {
        margin-bottom: 0;
    }
    
    .feature-icon {
        width: 40px;
        height: 40px;
        background-color: var(--light-purple);
        color: var(--primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        flex-shrink: 0;
    }
    
    .feature-content {
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .feature-label {
        font-size: 15px;
        color: var(--gray-dark);
    }
    
    .feature-value {
        font-size: 18px;
        font-weight: 700;
        color: var(--primary-dark);
    }
    
    .pricing-date {
        display: flex;
        align-items: center;
        padding: 15px;
        background-color: var(--light-purple);
        border-radius: var(--border-radius);
        font-size: 14px;
        color: var(--primary-dark);
    }
    
    .pricing-date i {
        margin-right: 10px;
        color: var(--primary);
    }
    
    .pricing-footer {
        padding: 20px 30px 30px;
    }
    
    .btn-select {
        display: block;
        width: 100%;
        padding: 12px;
        background-color: var(--white);
        color: var(--primary);
        border: 2px solid var(--primary);
        border-radius: 6px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        text-align: center;
        text-decoration: none;
    }
    
    .btn-select:hover {
        background-color: var(--primary);
        color: var(--white);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(122, 31, 160, 0.2);
    }
    
    /* Special Offers */
    .offers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
        gap: 30px;
    }
    
    .offer-card {
        background-color: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 30px;
        text-align: center;
        transition: var(--transition);
        border: 1px solid var(--gray-light);
    }
    
    .offer-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
    }
    
    .offer-icon {
        width: 70px;
        height: 70px;
        background-color: var(--light-purple);
        color: var(--primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 24px;
    }
    
    .offer-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--primary-dark);
        margin-bottom: 15px;
    }
    
    .offer-description {
        font-size: 14px;
        color: var(--gray-dark);
        line-height: 1.6;
    }
    
    /* FAQ Section */
    .faq-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .faq-item {
        background-color: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        margin-bottom: 15px;
        overflow: hidden;
        border: 1px solid var(--gray-light);
    }
    
    .faq-question {
        padding: 20px 25px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: var(--transition);
    }
    
    .faq-question:hover {
        background-color: var(--light-purple);
    }
    
    .faq-question h3 {
        font-size: 16px;
        font-weight: 600;
        color: var(--dark);
        margin: 0;
    }
    
    .faq-icon {
        color: var(--primary);
        transition: var(--transition);
    }
    
    .faq-answer {
        padding: 0 25px;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease, padding 0.3s ease;
    }
    
    .faq-answer p {
        color: var(--gray-dark);
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 20px;
    }
    
    .faq-item.active .faq-question {
        background-color: var(--light-purple);
    }
    
    .faq-item.active .faq-icon {
        transform: rotate(180deg);
    }
    
    .faq-item.active .faq-answer {
        max-height: 200px;
        padding: 15px 25px 20px;
    }
    
    /* Call to Action */
    .cta-section {
        padding: 50px;
        background: linear-gradient(135deg, var(--primary-dark), var(--primary-light));
        border-radius: var(--border-radius);
        text-align: center;
        color: var(--white);
        margin-top: 40px;
    }
    
    .cta-content h2 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 15px;
    }
    
    .cta-content p {
        font-size: 18px;
        margin-bottom: 30px;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        opacity: 0.9;
    }
    
    .cta-button {
        display: inline-block;
        padding: 12px 30px;
        background-color: var(--white);
        color: var(--primary);
        border-radius: 30px;
        font-weight: 600;
        text-decoration: none;
        transition: var(--transition);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .cta-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }
    
    /* Error and Empty States */
    .error-message {
        background-color: rgba(231, 76, 60, 0.1);
        color: var(--danger);
        padding: 15px 20px;
        border-radius: var(--border-radius);
        margin-bottom: 30px;
        display: flex;
        align-items: center;
    }
    
    .error-message i {
        margin-right: 10px;
        font-size: 20px;
    }
    
    .empty-state {
        padding: 40px;
        text-align: center;
        background-color: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
    }
    
    .empty-icon {
        font-size: 40px;
        color: var(--gray);
        margin-bottom: 20px;
    }
    
    .empty-state p {
        color: var(--gray-dark);
        font-size: 16px;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 992px) {
        .pricing-cards {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }
    }
    
    @media (max-width: 768px) {
        .main-content {
            padding: 100px 0 60px;
        }
        
        .page-title {
            font-size: 32px;
        }
        
        .section-title {
            font-size: 24px;
        }
        
        .pricing-cards {
            grid-template-columns: 1fr;
        }
        
        .offers-grid {
            grid-template-columns: 1fr;
        }
        
        .cta-section {
            padding: 40px 25px;
        }
        
        .cta-content h2 {
            font-size: 26px;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>

    <script>
    // Toggle FAQ items
    function toggleFaq(element) {
        const item = element.parentElement;
        if (item.classList.contains('active')) {
            item.classList.remove('active');
        } else {
            // Close any open FAQs
            document.querySelectorAll('.faq-item').forEach(el => {
                el.classList.remove('active');
            });
            item.classList.add('active');
        }
    }
    
    // Rate card selection
        function selectRate(rateName) {
        const cards = document.querySelectorAll('.pricing-card');
        cards.forEach(card => {
            const title = card.querySelector('.pricing-title').textContent;
            if (title === rateName) {
                card.classList.add('selected');
                // Add a highlight effect
                card.style.border = '2px solid var(--primary)';
                card.style.boxShadow = '0 15px 30px rgba(122, 31, 160, 0.2)';
            } else {
                card.classList.remove('selected');
                card.style.border = '1px solid var(--gray-light)';
                card.style.boxShadow = 'var(--box-shadow)';
            }
        });
    }
    
    // Open first FAQ item by default
    document.addEventListener('DOMContentLoaded', function() {
        const firstFaq = document.querySelector('.faq-item');
        if (firstFaq) {
            firstFaq.classList.add('active');
        }
    });
    </script>