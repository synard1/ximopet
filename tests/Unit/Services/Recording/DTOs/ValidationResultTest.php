<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Recording\DTOs;

use App\Services\Recording\DTOs\ValidationResult;
use Tests\TestCase;

class ValidationResultTest extends TestCase
{
    public function test_creates_successful_validation_result()
    {
        $result = ValidationResult::success('Test message', ['key' => 'value']);

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
        $this->assertEmpty($result->warnings);
        $this->assertEquals('Test message', $result->message);
        $this->assertEquals(['key' => 'value'], $result->metadata);
    }

    public function test_creates_failure_validation_result()
    {
        $errors = ['Error 1', 'Error 2'];
        $warnings = ['Warning 1'];
        $result = ValidationResult::failure($errors, 'Test failure', $warnings, ['key' => 'value']);

        $this->assertFalse($result->isValid);
        $this->assertEquals($errors, $result->errors);
        $this->assertEquals($warnings, $result->warnings);
        $this->assertEquals('Test failure', $result->message);
        $this->assertEquals(['key' => 'value'], $result->metadata);
    }

    public function test_creates_from_exception()
    {
        $exception = new \Exception('Test exception');
        $result = ValidationResult::fromException($exception);

        $this->assertFalse($result->isValid);
        $this->assertEquals(['Test exception'], $result->errors);
        $this->assertEquals('Validation failed due to exception', $result->message);
        $this->assertEquals(['exception_class' => 'Exception'], $result->metadata);
    }

    public function test_has_errors_detection()
    {
        $result = ValidationResult::failure(['Error 1']);
        $this->assertTrue($result->hasErrors());

        $result = ValidationResult::success();
        $this->assertFalse($result->hasErrors());
    }

    public function test_has_warnings_detection()
    {
        $result = ValidationResult::failure(['Error 1'], null, ['Warning 1']);
        $this->assertTrue($result->hasWarnings());

        $result = ValidationResult::success();
        $this->assertFalse($result->hasWarnings());
    }

    public function test_first_error_retrieval()
    {
        $result = ValidationResult::failure(['First error', 'Second error']);
        $this->assertEquals('First error', $result->getFirstError());

        $result = ValidationResult::success();
        $this->assertNull($result->getFirstError());
    }

    public function test_errors_as_string()
    {
        $result = ValidationResult::failure(['Error 1', 'Error 2']);
        $this->assertEquals('Error 1; Error 2', $result->getFormattedErrors());
    }

    public function test_warnings_as_string()
    {
        $result = ValidationResult::failure(['Error 1'], null, ['Warning 1', 'Warning 2']);
        $this->assertEquals('Warning 1; Warning 2', $result->getFormattedWarnings());
    }

    public function test_merge_validation_results()
    {
        $result1 = ValidationResult::successWithWarnings(['Warning 1']);
        $result2 = ValidationResult::failure(['Error 1'], null, ['Warning 2']);

        $merged = ValidationResult::merge($result1, $result2);

        $this->assertFalse($merged->isValid);
        $this->assertEquals(['Error 1'], $merged->errors);
        $this->assertEquals(['Warning 1', 'Warning 2'], $merged->warnings);
    }

    public function test_to_array_conversion()
    {
        $result = ValidationResult::failure(['Error 1'], 'Test message', ['Warning 1'], ['key' => 'value']);
        $array = $result->toArray();

        // Check main fields
        $this->assertFalse($array['is_valid']);
        $this->assertEquals(['Error 1'], $array['errors']);
        $this->assertEquals(['Warning 1'], $array['warnings']);
        $this->assertEquals('Test message', $array['message']);
        $this->assertEquals(['key' => 'value'], $array['metadata']);

        // Check additional computed fields
        $this->assertTrue($array['has_errors']);
        $this->assertTrue($array['has_warnings']);
        $this->assertEquals('Error 1', $array['first_error']);
        $this->assertEquals('Warning 1', $array['first_warning']);
    }

    public function test_to_json_conversion()
    {
        $result = ValidationResult::success('Test message');
        $json = $result->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertTrue($decoded['is_valid']);
        $this->assertEquals('Test message', $decoded['message']);
    }

    public function test_from_array_creation()
    {
        $array = [
            'is_valid' => false,
            'errors' => ['Error 1'],
            'warnings' => ['Warning 1'],
            'message' => 'Test message',
            'metadata' => ['key' => 'value']
        ];

        $result = ValidationResult::fromArray($array);

        $this->assertFalse($result->isValid);
        $this->assertEquals(['Error 1'], $result->errors);
        $this->assertEquals(['Warning 1'], $result->warnings);
        $this->assertEquals('Test message', $result->message);
        $this->assertEquals(['key' => 'value'], $result->metadata);
    }

    public function test_get_summary()
    {
        $result = ValidationResult::success();
        $this->assertEquals('VALID', $result->getSummary());

        $result = ValidationResult::failure(['Error 1'], null, ['Warning 1']);
        $this->assertEquals('INVALID - 1 error(s) - 1 warning(s)', $result->getSummary());
    }

    public function test_get_formatted_errors_array()
    {
        $result = ValidationResult::failure(['Error 1', 'Error 2']);
        $formatted = $result->getFormattedErrorsArray();

        $this->assertEquals(['[1] Error 1', '[2] Error 2'], $formatted);
    }

    public function test_get_formatted_warnings_array()
    {
        $result = ValidationResult::failure(['Error 1'], null, ['Warning 1', 'Warning 2']);
        $formatted = $result->getFormattedWarningsArray();

        $this->assertEquals(['[1] Warning 1', '[2] Warning 2'], $formatted);
    }

    public function test_is_perfect_method()
    {
        $result = ValidationResult::success();
        $this->assertTrue($result->isPerfect());

        $result = ValidationResult::successWithWarnings(['Warning']);
        $this->assertFalse($result->isPerfect());

        $result = ValidationResult::failure(['Error']);
        $this->assertFalse($result->isPerfect());
    }

    public function test_get_score()
    {
        $result = ValidationResult::success();
        $this->assertEquals(100, $result->getScore());

        $result = ValidationResult::successWithWarnings(['Warning']);
        $this->assertEquals(80, $result->getScore());

        $result = ValidationResult::failure(['Error']);
        $this->assertEquals(40, $result->getScore());

        $result = ValidationResult::failure(['E1', 'E2', 'E3', 'E4', 'E5', 'E6']);
        $this->assertEquals(0, $result->getScore());
    }

    public function test_get_metadata_value()
    {
        $result = ValidationResult::success('', ['key' => 'value', 'number' => 42]);

        $this->assertEquals('value', $result->getMetadata('key'));
        $this->assertEquals(42, $result->getMetadata('number'));
        $this->assertEquals('default', $result->getMetadata('missing', 'default'));
    }

    public function test_has_metadata()
    {
        $result = ValidationResult::success('', ['key' => 'value']);

        $this->assertTrue($result->hasMetadata('key'));
        $this->assertFalse($result->hasMetadata('missing'));
    }

    public function test_get_counts()
    {
        $result = ValidationResult::failure(['E1', 'E2'], null, ['W1', 'W2', 'W3']);

        $this->assertEquals(2, $result->getErrorCount());
        $this->assertEquals(3, $result->getWarningCount());
    }

    public function test_for_field_creation()
    {
        $result = ValidationResult::forField('username', false, ['Username is required']);

        $this->assertFalse($result->isValid);
        $this->assertEquals(['Username is required'], $result->errors);
        $this->assertEquals('username', $result->getMetadata('field'));
    }

    public function test_for_fields_creation()
    {
        $fieldResults = [
            'username' => ValidationResult::forField('username', true),
            'email' => ValidationResult::forField('email', false, ['Email is invalid'])
        ];

        $result = ValidationResult::forFields($fieldResults);

        $this->assertFalse($result->isValid);
        $this->assertEquals(['Email is invalid'], $result->errors);
        $this->assertArrayHasKey('fields', $result->metadata);
    }

    public function test_to_string_conversion()
    {
        $result = ValidationResult::success();
        $this->assertEquals('VALID', (string) $result);

        $result = ValidationResult::failure(['Error']);
        $this->assertEquals('INVALID - 1 error(s)', (string) $result);
    }
}
