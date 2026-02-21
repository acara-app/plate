<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    */

    'login' => [
        'page_title' => 'Acara Plate-д нэвтрэх | Чихрийн шижингийн хиймэл оюуны хооллолт',
        'title' => 'Нэвтрэх',
        'description' => 'Имэйл болон нууц үгээ оруулан нэвтэрнэ үү',
        'email' => 'Имэйл хаяг',
        'email_placeholder' => 'email@example.com',
        'password' => 'Нууц үг',
        'password_placeholder' => 'Нууц үг',
        'remember_me' => 'Намайг санах',
        'submit' => 'Нэвтрэх',
        'forgot_password' => 'Нууц үгээ мартсан уу?',
        'no_account' => "Бүртгэлгүй юу?",
        'sign_up' => 'Бүртгүүлэх',
        'or' => 'Эсвэл',
    ],

    'register' => [
        'page_title' => 'Acara Plate бүртгэл үүсгэх | Аяллаа эхлүүлээрэй',
        'title' => 'Бүртгэл үүсгэх',
        'description' => 'Мэдээллээ оруулан шинээр бүртгүүлнэ үү',
        'name' => 'Нэр',
        'name_placeholder' => 'Бүтэн нэр',
        'email' => 'Имэйл хаяг',
        'email_placeholder' => 'email@example.com',
        'password' => 'Нууц үг',
        'password_placeholder' => 'Нууц үг',
        'password_confirmation' => 'Нууц үг баталгаажуулах',
        'password_confirmation_placeholder' => 'Нууц үг баталгаажуулах',
        'submit' => 'Бүртгэл үүсгэх',
        'already_have_account' => 'Бүртгэлтэй юу?',
        'log_in' => 'Нэвтрэх',
        'or' => 'Эсвэл',
        'terms_acceptance' => 'Бүртгүүлснээр би 18 нас хүрсэн гэдгээ баталж, дараах нөхцөлийг хүлээн зөвшөөрч байна:',
        'terms_of_service' => 'Үйлчилгээний нөхцөл',
        'and' => 'ба',
        'privacy_policy' => 'Нууцлалын бодлого',
    ],

    'password_reset' => [
        'request_page_title' => 'Нууц үг сэргээх | Acara Plate Тусламж',
        'reset_page_title' => 'Шинэ нууц үг үүсгэх | Acara Plate',
        'request_title' => 'Нууц үгээ мартсан',
        'request_description' => 'Имэйлээ оруулж нууц үг сэргээх холбоос хүлээн авна уу',
        'email' => 'Имэйл хаяг',
        'email_placeholder' => 'email@example.com',
        'submit' => 'Баталгаажуулах холбоос илгээх',
        'back_to_login' => 'Буцах',
        'reset_title' => 'Нууц үг сэргээх',
        'reset_description' => 'Шинэ нууц үгээ оруулна уу',
        'password' => 'Нууц үг',
        'password_placeholder' => 'Нууц үг',
        'password_confirmation' => 'Нууц үгээ баталгаажуулах',
        'password_confirmation_placeholder' => 'Нууц үгээ баталгаажуулах',
        'reset_submit' => 'Нууц үг шинэчлэх',
    ],

    'two_factor' => [
        'title' => 'Хоёр алхамт баталгаажуулалт',
        'description' => 'Баталгаажуулах апп-аас өгсөн кодыг оруулан нэвтэрнэ үү.',
        'code' => 'Код',
        'code_placeholder' => 'Баталгаажуулах код',
        'recovery_code' => 'Сэргээх код',
        'recovery_code_placeholder' => 'Сэргээх код',
        'use_recovery_code' => 'Сэргээх код ашиглах',
        'use_authentication_code' => 'Баталгаажуулах код ашиглах',
        'submit' => 'Нэвтрэх',
    ],

    'email_verification' => [
        'page_title' => 'Имэйл баталгаажуулах | Acara Plate',
        'title' => 'Имэйл баталгаажуулах',
        'description' => 'Таны имэйл рүү илгээсэн холбоос дээр дарж имэйлээ баталгаажуулна уу.',
        'resend' => 'Баталгаажуулах имэйл дахин илгээх',
        'logout' => 'Гарах',
        'verification_sent' => 'Бүртгүүлэх үед оруулсан имэйл хаяг руу шинэ баталгаажуулах холбоос илгээлээ.',
    ],

    'password_confirmation' => [
        'title' => 'Нууц үгээ баталгаажуулах',
        'description' => 'Үргэлжлүүлэхийн тулд нууц үгээ дахин оруулна уу. Энэ нь аюулгүй байдлын үүднээс шаардлагатай.',
        'page_title' => 'Нууц үг баталгаажуулах',
        'password_label' => 'Нууц үг',
        'password_placeholder' => 'Нууц үг',
        'confirm_button' => 'Нууц үг баталгаажуулах',
    ],

    'two_factor_settings' => [
        'title' => 'Хоёр алхамт баталгаажуулалт',
        'description' => 'Хоёр алхамт баталгаажуулалтын тохиргоогоо удирдах',
        'enabled_badge' => 'Идэвхжсэн',
        'disabled_badge' => 'Идэвхжээгүй',
        'enabled_description' => 'Хоёр алхамт баталгаажуулалт идэвхжсэнээр та нэвтрэх үедээ гар утсан дээрх апп-аас олгох аюулгүй байдлын кодыг оруулах шаардлагатай болно.',
        'disabled_description' => 'Хоёр алхамт баталгаажуулалт идэвхжүүлснээр та нэвтрэх үедээ гар утсан дээрх апп-аас (TOTP) код оруулах шаардлагатай болно.',
        'enable_button' => '2FA идэвхжүүлэх',
        'disable_button' => '2FA идэвхгүй болгох',
        'continue_setup' => 'Тохиргоог үргэлжлүүлэх',
    ],

    'two_factor_challenge' => [
        'title' => 'Хоёр алхамт баталгаажуулалт',
        'code_title' => 'Баталгаажуулах код',
        'code_description' => 'Баталгаажуулах апп-аас өгсөн кодыг оруулна уу.',
        'recovery_title' => 'Сэргээх код',
        'recovery_description' => 'Яаралтай үед ашиглах сэргээх кодуудын нэгийг оруулан нэвтэрнэ үү.',
        'recovery_placeholder' => 'Сэргээх код оруулах',
        'continue_button' => 'Үргэлжлүүлэх',
        'or_you_can' => 'эсвэл',
        'use_recovery_code' => 'сэргээх код ашиглан нэвтрэх',
        'use_auth_code' => 'баталгаажуулах код ашиглан нэвтрэх',
    ],

    'failed' => 'Мэдээлэл буруу байна.',
    'password' => 'Нууц үг буруу байна.',
    'throttle' => 'Хэт олон удаа оролдлоо. :seconds секундийн дараа дахин оролдоно уу.',
];
