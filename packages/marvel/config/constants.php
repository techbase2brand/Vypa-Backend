<?php

if (!defined('APP_NOTICE_DOMAIN')) {
    define('APP_NOTICE_DOMAIN', 'VYPA_');
}
if (!defined('DEFAULT_LANGUAGE')) {
    define('DEFAULT_LANGUAGE', config('shop.default_language'));
}
if (!defined('TRANSLATION_ENABLED')) {
    define('TRANSLATION_ENABLED', config('shop.translation_enabled'));
}
if (!defined('DEFAULT_CURRENCY')) {
    define('DEFAULT_CURRENCY', config('shop.default_currency'));
}
if (!defined('DEFAULT_CURRENCY_FORMATION')) {
    define('DEFAULT_CURRENCY_FORMATION', 'en-US');
}
if (!defined('ACTIVE_PAYMENT_GATEWAY')) {
    define('ACTIVE_PAYMENT_GATEWAY', config('shop.active_payment_gateway'));
}

// Error and message constants
$constants = [
    'NOT_FOUND' => APP_NOTICE_DOMAIN . 'ERROR.NOT_FOUND',
    'COUPON_NOT_FOUND' => APP_NOTICE_DOMAIN . 'ERROR.COUPON_NOT_FOUND',
    'INVALID_COUPON_CODE' => APP_NOTICE_DOMAIN . 'ERROR.INVALID_COUPON_CODE',
    'COUPON_CODE_IS_NOT_APPLICABLE' => APP_NOTICE_DOMAIN . 'ERROR.COUPON_CODE_IS_NOT_APPLICABLE',
    'ALREADY_FREE_SHIPPING_ACTIVATED' => APP_NOTICE_DOMAIN . 'ERROR.ALREADY_FREE_SHIPPING_ACTIVATED',
    'CART_ITEMS_NOT_FOUND' => APP_NOTICE_DOMAIN . 'ERROR.CART_ITEMS_NOT_FOUND',
    'NOT_A_RENTAL_PRODUCT' => APP_NOTICE_DOMAIN . 'ERROR.NOT_A_RENTAL_PRODUCT',
    'NOT_AUTHORIZED' => APP_NOTICE_DOMAIN . 'ERROR.NOT_AUTHORIZED',
    'SOMETHING_WENT_WRONG' => APP_NOTICE_DOMAIN . 'ERROR.SOMETHING_WENT_WRONG',
    'PAYMENT_FAILED' => APP_NOTICE_DOMAIN . 'ERROR.PAYMENT_FAILED',
    'SHOP_NOT_APPROVED' => APP_NOTICE_DOMAIN . 'ERROR.SHOP_NOT_APPROVED',
    'INSUFFICIENT_BALANCE' => APP_NOTICE_DOMAIN . 'ERROR.INSUFFICIENT_BALANCE',
    'INVALID_CREDENTIALS' => APP_NOTICE_DOMAIN . 'ERROR.INVALID_CREDENTIALS',
    'EMAIL_SENT_SUCCESSFUL' => APP_NOTICE_DOMAIN . 'MESSAGE.EMAIL_SENT_SUCCESSFUL',
    'PASSWORD_RESET_SUCCESSFUL' => APP_NOTICE_DOMAIN . 'MESSAGE.PASSWORD_RESET_SUCCESSFUL',
    'INVALID_TOKEN' => APP_NOTICE_DOMAIN . 'MESSAGE.INVALID_TOKEN',
    'TOKEN_IS_VALID' => APP_NOTICE_DOMAIN . 'MESSAGE.TOKEN_IS_VALID',
    'CHECK_INBOX_FOR_PASSWORD_RESET_EMAIL' => APP_NOTICE_DOMAIN . 'MESSAGE.CHECK_INBOX_FOR_PASSWORD_RESET_EMAIL',
    'ACTION_NOT_VALID' => APP_NOTICE_DOMAIN . 'ERROR.ACTION_NOT_VALID',
    'PLEASE_LOGIN_USING_FACEBOOK_OR_GOOGLE' => APP_NOTICE_DOMAIN . 'ERROR.PLEASE_LOGIN_USING_FACEBOOK_OR_GOOGLE',
    'WITHDRAW_MUST_BE_ATTACHED_TO_SHOP' => APP_NOTICE_DOMAIN . 'ERROR.WITHDRAW_MUST_BE_ATTACHED_TO_SHOP',
    'OLD_PASSWORD_INCORRECT' => APP_NOTICE_DOMAIN . 'MESSAGE.OLD_PASSWORD_INCORRECT',
    'OTP_SEND_FAIL' => APP_NOTICE_DOMAIN . 'ERROR.OTP_SEND_FAIL',
    'OTP_SEND_SUCCESSFUL' => APP_NOTICE_DOMAIN . 'MESSAGE.OTP_SEND_SUCCESSFUL',
    'REQUIRED_INFO_MISSING' => APP_NOTICE_DOMAIN . 'MESSAGE.REQUIRED_INFO_MISSING',
    'CONTACT_UPDATE_SUCCESSFUL' => APP_NOTICE_DOMAIN . 'MESSAGE.CONTACT_UPDATE_SUCCESSFUL',
    'INVALID_GATEWAY' => APP_NOTICE_DOMAIN . 'ERROR.INVALID_GATEWAY',
    'OTP_VERIFICATION_FAILED' => APP_NOTICE_DOMAIN . 'ERROR.OTP_VERIFICATION_FAILED',
    'CONTACT_UPDATE_FAILED' => APP_NOTICE_DOMAIN . 'ERROR.CONTACT_UPDATE_FAILED',
    'ALREADY_REFUNDED' => APP_NOTICE_DOMAIN . 'ERROR.ALREADY_REFUNDED',
    'ORDER_ALREADY_HAS_REFUND_REQUEST' => APP_NOTICE_DOMAIN . 'ERROR.ORDER_ALREADY_HAS_REFUND_REQUEST',
    'REFUND_ONLY_ALLOWED_FOR_MAIN_ORDER' => APP_NOTICE_DOMAIN . 'ERROR.REFUND_ONLY_ALLOWED_FOR_MAIN_ORDER',
    'WRONG_REFUND' => APP_NOTICE_DOMAIN . 'ERROR.WRONG_REFUND',
    'CSV_NOT_FOUND' => APP_NOTICE_DOMAIN . 'ERROR.CSV_NOT_FOUND',
    'ALREADY_GIVEN_REVIEW_FOR_THIS_PRODUCT' => APP_NOTICE_DOMAIN . 'ERROR.ALREADY_GIVEN_REVIEW_FOR_THIS_PRODUCT',
    'USER_NOT_FOUND' => APP_NOTICE_DOMAIN . 'ERROR.USER_NOT_FOUND',
    'TOKEN_NOT_FOUND' => APP_NOTICE_DOMAIN . 'ERROR.TOKEN_NOT_FOUND',
    'NOT_AVAILABLE_FOR_BOOKING' => APP_NOTICE_DOMAIN . 'ERROR.NOT_AVAILABLE_FOR_BOOKING',
    'YOU_HAVE_ALREADY_GIVEN_ABUSIVE_REPORT_FOR_THIS' => APP_NOTICE_DOMAIN . 'ERROR.YOU_HAVE_ALREADY_GIVEN_ABUSIVE_REPORT_FOR_THIS',
    'MAXIMUM_QUESTION_LIMIT_EXCEEDED' => APP_NOTICE_DOMAIN . 'ERROR.MAXIMUM_QUESTION_LIMIT_EXCEEDED',
    'INVALID_AMOUNT' => APP_NOTICE_DOMAIN . 'ERROR.INVALID_AMOUNT',
    'INVALID_CARD' => APP_NOTICE_DOMAIN . 'ERROR.INVALID_CARD',
    'TOO_MANY_REQUEST' => APP_NOTICE_DOMAIN . 'ERROR.TOO_MANY_REQUEST',
    'INVALID_REQUEST' => APP_NOTICE_DOMAIN . 'ERROR.INVALID_REQUEST',
    'AUTHENTICATION_FAILED' => APP_NOTICE_DOMAIN . 'ERROR.AUTHENTICATION_FAILED',
    'API_CONNECTION_FAILED' => APP_NOTICE_DOMAIN . 'ERROR.API_CONNECTION_FAILED',
    'SOMETHING_WENT_WRONG_WITH_PAYMENT' => APP_NOTICE_DOMAIN . 'ERROR.SOMETHING_WENT_WRONG_WITH_PAYMENT',
    'INVALID_PAYMENT_ID' => APP_NOTICE_DOMAIN . 'ERROR.INVALID_PAYMENT_ID',
    'INVALID_PAYMENT_INTENT_ID' => APP_NOTICE_DOMAIN . 'ERROR.INVALID_PAYMENT_INTENT_ID',
    'EMAIL_NOT_VERIFIED' => 'EMAIL_NOT_VERIFIED',
    'INVALID_LICENSE_KEY' => 'INVALID_LICENSE_KEY',
    'EMAIL_UPDATED_SUCCESSFULLY' => APP_NOTICE_DOMAIN . 'SUCCESS.EMAIL_UPDATED_SUCCESSFULLY',
    'YOU_CAN_NOT_SEND_MESSAGE_TO_YOUR_OWN_SHOP' => APP_NOTICE_DOMAIN . 'ERROR.YOU_CAN_NOT_SEND_MESSAGE_TO_YOUR_OWN_SHOP',
    'COULD_NOT_CREATE_THE_RESOURCE' => APP_NOTICE_DOMAIN . 'ERROR.COULD_NOT_CREATE_THE_RESOURCE',
    'COULD_NOT_UPDATE_THE_RESOURCE' => APP_NOTICE_DOMAIN . 'ERROR.COULD_NOT_UPDATE_THE_RESOURCE',
    'COULD_NOT_DELETE_THE_RESOURCE' => APP_NOTICE_DOMAIN . 'ERROR.COULD_NOT_DELETE_THE_RESOURCE',
    'PLEASE_ENABLE_OPENAI_FROM_THE_SETTINGS' => APP_NOTICE_DOMAIN . 'ERROR.PLEASE_ENABLE_OPENAI_FROM_THE_SETTINGS',
    'DUMMY_DATA_PATH' => config('shop.dummy_data_path'),
    'THIS_COUPON_CODE_IS_ONLY_FOR_VERIFIED_USERS' => APP_NOTICE_DOMAIN . 'ERROR.THIS_COUPON_CODE_IS_ONLY_FOR_VERIFIED_USERS',
    'THIS_COUPON_CODE_IS_NOT_APPROVED' => APP_NOTICE_DOMAIN . 'ERROR.THIS_COUPON_CODE_IS_NOT_APPROVED',
    'COUPON_CODE_IS_NOT_APPLICABLE_IN_THIS_SHOP_PRODUCT' => APP_NOTICE_DOMAIN . 'ERROR.COUPON_CODE_IS_NOT_APPLICABLE_IN_THIS_SHOP_PRODUCT',
    'PLEASE_ENABLE_PAYMENT_OPTION_FROM_THE_SETTINGS' => APP_NOTICE_DOMAIN . 'ERROR.PLEASE_ENABLE_PAYMENT_OPTION_FROM_THE_SETTINGS',
    'COULD_NOT_SETTLE_THE_TRANSITION' => APP_NOTICE_DOMAIN . 'ERROR.COULD_NOT_SETTLE_THE_TRANSITION',
];

// Loop through constants and define if not already defined
foreach ($constants as $key => $value) {
    if (!defined($key)) {
        define($key, $value);
    }
}
?>
