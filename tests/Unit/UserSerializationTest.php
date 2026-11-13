<?php

use App\Models\User;

test('user JSON excludes login and two factor credentials', function () {
    $user = new User;
    $user->setRawAttributes([
        'name' => 'Serialization Test',
        'email' => 'serialization@example.test',
        'password' => 'dummy-password-hash',
        'remember_token' => 'dummy-remember-token',
        'two_factor_secret' => 'dummy-two-factor-secret',
        'two_factor_recovery_codes' => 'dummy-recovery-codes',
    ]);

    $data = json_decode($user->toJson(), true, flags: JSON_THROW_ON_ERROR);

    expect($data)->toBe([
        'name' => 'Serialization Test',
        'email' => 'serialization@example.test',
    ]);
});
