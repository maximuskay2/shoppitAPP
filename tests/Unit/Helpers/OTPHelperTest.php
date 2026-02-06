<?php

namespace Tests\Unit\Helpers;

use App\Helpers\OTPHelper;
use PHPUnit\Framework\TestCase;

class OTPHelperTest extends TestCase
{
    /**
     * Test OTP generation produces numeric strings
     */
    public function test_generate_produces_numeric_string()
    {
        $otp = OTPHelper::generate(6);

        $this->assertEquals(6, strlen($otp));
        $this->assertTrue(ctype_digit($otp));
    }

    /**
     * Test OTP generation with custom length
     */
    public function test_generate_with_custom_length()
    {
        $otp4 = OTPHelper::generate(4);
        $otp8 = OTPHelper::generate(8);
        $otp10 = OTPHelper::generate(10);

        $this->assertEquals(4, strlen($otp4));
        $this->assertEquals(8, strlen($otp8));
        $this->assertEquals(10, strlen($otp10));
    }

    /**
     * Test OTP generation respects min/max length constraints
     */
    public function test_generate_respects_length_constraints()
    {
        // Too short
        $otpShort = OTPHelper::generate(2);
        $this->assertEquals(OTPHelper::MIN_LENGTH, strlen($otpShort));

        // Too long  
        $otpLong = OTPHelper::generate(15);
        $this->assertEquals(OTPHelper::MAX_LENGTH, strlen($otpLong));
    }

    /**
     * Test OTP format validation
     */
    public function test_validate_format()
    {
        $this->assertTrue(OTPHelper::validateFormat('123456'));
        $this->assertFalse(OTPHelper::validateFormat('12345a'));
        $this->assertFalse(OTPHelper::validateFormat(''));
        $this->assertFalse(OTPHelper::validateFormat('abc'));
    }

    /**
     * Test OTP length validation
     */
    public function test_validate_length()
    {
        $this->assertTrue(OTPHelper::validateLength('1234'));
        $this->assertTrue(OTPHelper::validateLength('123456'));
        $this->assertTrue(OTPHelper::validateLength('1234567890'));
        
        $this->assertFalse(OTPHelper::validateLength('123'));      // Too short
        $this->assertFalse(OTPHelper::validateLength('12345678901')); // Too long
    }

    /**
     * Test complete OTP validation
     */
    public function test_validate_complete()
    {
        $this->assertTrue(OTPHelper::validate('123456'));
        $this->assertTrue(OTPHelper::validate('1234'));
        $this->assertTrue(OTPHelper::validate('1234567890'));
        
        $this->assertFalse(OTPHelper::validate('123'));       // Too short
        $this->assertFalse(OTPHelper::validate('12345678901')); // Too long
        $this->assertFalse(OTPHelper::validate('12345a'));    // Non-numeric
    }

    /**
     * Test OTP comparison (constant-time)
     */
    public function test_compare()
    {
        $otp1 = '123456';
        $otp2 = '123456';
        $otp3 = '654321';

        $this->assertTrue(OTPHelper::compare($otp1, $otp2));
        $this->assertFalse(OTPHelper::compare($otp1, $otp3));
    }

    /**
     * Test OTP generation randomness (not repeating)
     */
    public function test_generate_is_random()
    {
        $otps = array_map(fn() => OTPHelper::generate(6), range(1, 10));

        // Check that not all OTPs are the same
        $uniqueOtps = array_unique($otps);
        $this->assertGreaterThan(1, count($uniqueOtps));
    }
}
