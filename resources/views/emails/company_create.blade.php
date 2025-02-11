
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<div style="width: 600px; border: 1px solid #ccc; padding: 20px; font-family: system-ui; border-radius: 10px;">
    <div class="logo" style="text-align: center; margin-bottom: 20px; margin-top: 10px;">
        <img src="{{ asset('images/logo.svg') }}" style="width: 100px;" />
    </div>
    <div class="banner" style="position:relative">
        <img src="{{ asset('images/banner.png') }}" style="width: 100%;" alt="">
        <h2 style="position: absolute;top: 38%; left: 20%;  transform: translate(-50%, -50%);color: #fff;">Thank You for <br /> Registering</h2>
    </div>

    <div class="details" style="margin-top: 30px; font-size: 14px;">
        <h2>Dear {{$company['name']??null}},</h2>
        <h4><b>Username : </b>{{$username}}</h4>
        <h4><b>Password : </b>{{$password}}</h4>
        <p>Thank you for choosing to register with VYPA! We’re excited to have you on board.</p>
        <p>Your registration is currently under review by our team. Once approved, you’ll receive an email notification, and your account will be ready for use. We are committed to providing you with top-quality workwear and safety products, delivered fast and reliably.</p>
        <p>We understand that you need workwear solutions that meet the demands of your business, and we’re here to provide just that. Your trust in us means a lot, and we’ll make sure to deliver an experience that exceeds your expectations.</p>
        <p>If you have any questions in the meantime, please don’t hesitate to reach out. You can contact us at <a style="color: #21BA21;"   href="tel:1300 585 202">1300 585 202</a> or email us at <a href="mailto:support@vypa.com" target="_blank" style="color: #21BA21;">support@vypa.com</a> .</p>
        <p>Thank you once again for choosing VYPA. We look forward to serving you!</p>

        <p style="margin-top: 2rem;margin: 5px 0px;"><b style="color: #333333;">Warm regards,</b></p>
        <p style="margin: 5px 0px;"><b style="color: #333333;">The VYPA Team</b></p>
        <p style="margin: 5px 0px;">VYPA - <a style="font-weight: 600; color: #333333;">Rail Workwear Supplier</a></p>
        <p style="margin: 5px 0px;">Phone: <a style="font-weight: 600;  color: #333333;" href="tel:1300 585 202">1300 585 202</a></p>
        <p style="margin: 5px 0px;">Email: <a style="font-weight: 600; color: #333333;" href="mailto:support@vypa.com" target="_blank">support@vypa.com</a></p>
        <p style="margin: 5px 0px;">Website: <a style="font-weight: 600;  color: #21BA21;" href="www.vypa.com" target="_blank">www.vypa.com</a></p>
    </div>

    <div class="footer" >
        <div class="icon" style="text-align: center; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; padding: 10px 0px; margin-top: 20px;">
            <img src="{{ asset('images/instagram.svg') }}" alt="">
            <img src="{{ asset('images/facebook.svg') }}" alt="">
        </div>
        <div style="text-align: center;">

            <p style="font-size: 14px;">© 2025 VYPA. All rights reserved.</p>
            <p style="font-size: 14px;">You are receiving this mail because you registered to join the VYPA platform as a user or a creator. This also shows that you agree to our Terms of use and Privacy Policies. If you no longer want to receive mails from use, click the unsubscribe link below to unsubscribe.</p>
            <ul style="display: flex; justify-content: center; gap:30px; color: #333333;">
                <li>Privacy policy</li>
                <li>Terms of service</li>
                <li>Help center</li>
                <li>Unsubscribe</li>
            </ul>

        </div>
    </div>
</div>
</body>
</html>
