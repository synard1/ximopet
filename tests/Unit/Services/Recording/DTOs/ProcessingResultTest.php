<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Recording\DTOs;

use App\Services\Recording\DTOs\ProcessingResult;
use App\Services\Recording\DTOs\ValidationResult;
use Tests\TestCase;

class ProcessingResultTest extends TestCase
{
    public function test_creates_successful_result()
    {
        $result = ProcessingResult::success('test data', 'Operation completed');

        $this->assertTrue($result->success);
        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertEquals('test data', $result->data);
        $this->assertEquals('Operation completed', $result->message);
        $this->assertEmpty($result->errors);
        $this->assertEmpty($result->warnings);
        $this->assertEmpty($result->metadata);
    }

    public function test_creates_failure_result()
    {
        $errors = ['Error 1', 'Error 2'];
        $warnings = ['Warning 1'];
        $result = ProcessingResult::failure($errors, 'Operation failed', $warnings, ['key' => 'value']);

        $this->assertFalse($result->success);
        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
        $this->assertNull($result->data);
        $this->assertEquals('Operation failed', $result->message);
        $this->assertEquals($errors, $result->errors);
        $this->assertEquals($warnings, $result->warnings);
        $this->assertEquals(['key' => 'value'], $result->metadata);
    }

    public function test_creates_from_exception()
    {
        $exception = new \Exception('Test exception', 500);
        $result = ProcessingResult::fromException($exception);

        $this->assertFalse($result->success);
        $this->assertEquals(['Test exception'], $result->errors);
        $this->assertEquals('Operation failed due to exception', $result->message);
        $this->assertEquals('Exception', $result->metadata['exception_class']);
        $this->assertEquals(500, $result->metadata['exception_code']);
    }

    public function test_has_data_detection()
    {
        $resultWithData = ProcessingResult::success('test data');
        $this->assertTrue($resultWithData->hasData());

        $resultWithoutData = ProcessingResult::success();
        $this->assertFalse($resultWithoutData->hasData());

        $failureResult = ProcessingResult::failure(['Error']);
        $this->assertFalse($failureResult->hasData());
    }

    public function test_has_errors_detection()
    {
        $resultWithErrors = ProcessingResult::failure(['Error 1']);
        $this->assertTrue($resultWithErrors->hasErrors());

        $successResult = ProcessingResult::success();
        $this->assertFalse($successResult->hasErrors());
    }

    public function test_has_warnings_detection()
    {
        $resultWithWarnings = ProcessingResult::failure(['Error'], null, ['Warning']);
        $this->assertTrue($resultWithWarnings->hasWarnings());

        $resultWithoutWarnings = ProcessingResult::success();
        $this->assertFalse($resultWithoutWarnings->hasWarnings());
    }

    public function test_first_error_retrieval()
    {
        $result = ProcessingResult::failure(['First error', 'Second error']);
        $this->assertEquals('First error', $result->getFirstError());

        $successResult = ProcessingResult::success();
        $this->assertNull($successResult->getFirstError());
    }

    public function test_first_warning_retrieval()
    {
        $result = ProcessingResult::failure(['Error'], null, ['First warning', 'Second warning']);
        $this->assertEquals('First warning', $result->getFirstWarning());

        $resultWithoutWarnings = ProcessingResult::success();
        $this->assertNull($resultWithoutWarnings->getFirstWarning());
    }

    public function test_get_errors_and_warnings()
    {
        $errors = ['Error 1', 'Error 2'];
        $warnings = ['Warning 1', 'Warning 2'];
        $result = ProcessingResult::failure($errors, null, $warnings);

        $this->assertEquals($errors, $result->getErrors());
        $this->assertEquals($warnings, $result->getWarnings());
        $this->assertEquals(['Error 1', 'Error 2', 'Warning 1', 'Warning 2'], $result->getAllMessages());
    }

    public function test_formatted_errors_and_warnings()
    {
        $result = ProcessingResult::failure(['Error 1', 'Error 2'], null, ['Warning 1', 'Warning 2']);

        $this->assertEquals('Error 1; Error 2', $result->getFormattedErrors());
        $this->assertEquals('Warning 1; Warning 2', $result->getFormattedWarnings());

        $resultWithoutErrors = ProcessingResult::success();
        $this->assertEquals('', $resultWithoutErrors->getFormattedErrors());
        $this->assertEquals('', $resultWithoutErrors->getFormattedWarnings());
    }

    public function test_get_data_with_default()
    {
        $resultWithData = ProcessingResult::success('test data');
        $this->assertEquals('test data', $resultWithData->getData());
        $this->assertEquals('test data', $resultWithData->getData('default'));

        $resultWithoutData = ProcessingResult::success();
        $this->assertNull($resultWithoutData->getData());
        $this->assertEquals('default', $resultWithoutData->getData('default'));
    }

    public function test_with_data_method()
    {
        $originalResult = ProcessingResult::success();
        $newResult = $originalResult->withData('new data');

        $this->assertEquals('new data', $newResult->data);
        $this->assertNull($originalResult->data); // Original unchanged
    }

    public function test_with_error_method()
    {
        $originalResult = ProcessingResult::success();
        $newResult = $originalResult->withError('New error');

        $this->assertFalse($newResult->success);
        $this->assertEquals(['New error'], $newResult->errors);
        $this->assertTrue($originalResult->success); // Original unchanged
    }

    public function test_with_warning_method()
    {
        $originalResult = ProcessingResult::success();
        $newResult = $originalResult->withWarning('New warning');

        $this->assertTrue($newResult->success);
        $this->assertEquals(['New warning'], $newResult->warnings);
        $this->assertEmpty($originalResult->warnings); // Original unchanged
    }

    public function test_with_metadata_method()
    {
        $originalResult = ProcessingResult::success();
        $newResult = $originalResult->withMetadata(['key' => 'value']);

        $this->assertEquals(['key' => 'value'], $newResult->metadata);
        $this->assertEmpty($originalResult->metadata); // Original unchanged
    }

    public function test_with_message_method()
    {
        $originalResult = ProcessingResult::success();
        $newResult = $originalResult->withMessage('New message');

        $this->assertEquals('New message', $newResult->message);
        $this->assertNull($originalResult->message); // Original unchanged
    }

    public function test_merge_results()
    {
        $result1 = ProcessingResult::success('data1')->withWarning('Warning 1');
        $result2 = ProcessingResult::failure(['Error 1'], null, ['Warning 2']);

        $merged = ProcessingResult::merge($result1, $result2);

        $this->assertFalse($merged->success);
        $this->assertEquals(['Error 1'], $merged->errors);
        $this->assertEquals(['Warning 1', 'Warning 2'], $merged->warnings);
    }

    public function test_to_array_conversion()
    {
        $result = ProcessingResult::failure(['Error 1'], 'Failed', ['Warning 1'], ['key' => 'value']);
        $array = $result->toArray();

        // Check the main fields exist
        $this->assertFalse($array['success']);
        $this->assertNull($array['data']);
        $this->assertEquals('Failed', $array['message']);
        $this->assertEquals(['Error 1'], $array['errors']);
        $this->assertEquals(['Warning 1'], $array['warnings']);
        $this->assertEquals(['key' => 'value'], $array['metadata']);

        // Check additional computed fields
        $this->assertTrue($array['has_errors']);
        $this->assertTrue($array['has_warnings']);
        $this->assertEquals('FAILURE - 1 error(s) - 1 warning(s)', $array['summary']);
    }

    public function test_to_json_conversion()
    {
        $result = ProcessingResult::success('test', 'Success message');
        $json = $result->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertTrue($decoded['success']);
        $this->assertEquals('test', $decoded['data']);
        $this->assertEquals('Success message', $decoded['message']);
    }

    public function test_from_array_creation()
    {
        $array = [
            'success' => false,
            'data' => 'test data',
            'message' => 'Test message',
            'errors' => ['Error 1'],
            'warnings' => ['Warning 1'],
            'metadata' => ['key' => 'value']
        ];

        $result = ProcessingResult::fromArray($array);

        $this->assertFalse($result->success);
        $this->assertEquals('test data', $result->data);
        $this->assertEquals('Test message', $result->message);
        $this->assertEquals(['Error 1'], $result->errors);
        $this->assertEquals(['Warning 1'], $result->warnings);
        $this->assertEquals(['key' => 'value'], $result->metadata);
    }

    public function test_from_validation_creation()
    {
        $validationSuccess = ValidationResult::success('All good');
        $resultFromSuccess = ProcessingResult::fromValidation($validationSuccess, 'test data');

        $this->assertTrue($resultFromSuccess->success);
        $this->assertEquals('test data', $resultFromSuccess->data);
        $this->assertEquals('All good', $resultFromSuccess->message);

        $validationFailure = ValidationResult::failure(['Validation failed'], 'Failed', ['Warning']);
        $resultFromFailure = ProcessingResult::fromValidation($validationFailure);

        $this->assertFalse($resultFromFailure->success);
        $this->assertEquals(['Validation failed'], $resultFromFailure->errors);
        $this->assertEquals(['Warning'], $resultFromFailure->warnings);
        $this->assertEquals('Failed', $resultFromFailure->message);
    }

    public function test_get_summary()
    {
        $successResult = ProcessingResult::success();
        $this->assertEquals('SUCCESS', $successResult->getSummary());

        $failureResult = ProcessingResult::failure(['Error'], null, ['Warning']);
        $this->assertEquals('FAILURE - 1 error(s) - 1 warning(s)', $failureResult->getSummary());
    }

    public function test_get_metadata_value()
    {
        $result = ProcessingResult::success('', '', ['key' => 'value', 'number' => 42]);

        $this->assertEquals('value', $result->getMetadata('key'));
        $this->assertEquals(42, $result->getMetadata('number'));
        $this->assertEquals('default', $result->getMetadata('missing', 'default'));
    }

    public function test_has_metadata()
    {
        $result = ProcessingResult::success('', '', ['key' => 'value']);

        $this->assertTrue($result->hasMetadata('key'));
        $this->assertFalse($result->hasMetadata('missing'));
    }

    public function test_get_all_metadata()
    {
        $metadata = ['key1' => 'value1', 'key2' => 'value2'];
        $result = ProcessingResult::success('', '', $metadata);

        $this->assertEquals($metadata, $result->getAllMetadata());
    }

    public function test_performance_methods()
    {
        $result = ProcessingResult::success('', '', [
            'processing_duration' => 1.5,
            'processing_time' => 1.5,
            'memory_usage' => 1024,
            'affected_records' => 10,
            'affected_rows' => 10,
            'operation_type' => 'CREATE'
        ]);

        $this->assertEquals(1.5, $result->getProcessingDuration());
        $this->assertEquals(1024.0, $result->getMemoryUsage());
        $this->assertEquals(10, $result->getAffectedRecords());
        $this->assertEquals('CREATE', $result->getOperationType());
        $this->assertEquals(1.5, $result->getProcessingTime());
        $this->assertEquals(10, $result->getAffectedRows());
    }

    public function test_performance_analysis()
    {
        $fastResult = ProcessingResult::success('', '', ['processing_time' => 50.0]);
        $this->assertTrue($fastResult->isFast());

        $slowResult = ProcessingResult::success('', '', ['processing_time' => 200.0]);
        $this->assertFalse($slowResult->isFast());

        $lowMemoryResult = ProcessingResult::success('', '', ['memory_usage' => 512]);
        $this->assertTrue($lowMemoryResult->isLowMemory());

        $highMemoryResult = ProcessingResult::success('', '', ['memory_usage' => 2048 * 1024]);
        $this->assertFalse($highMemoryResult->isLowMemory());
    }

    public function test_efficiency_score()
    {
        $excellentResult = ProcessingResult::success('', '', [
            'processing_time' => 0.01,
            'memory_usage' => 100
        ]);
        $this->assertGreaterThanOrEqual(50, $excellentResult->getEfficiencyScore());

        $poorResult = ProcessingResult::success('', '', [
            'processing_time' => 5.0,
            'memory_usage' => 10 * 1024 * 1024
        ]);
        $this->assertLessThanOrEqual(100, $poorResult->getEfficiencyScore());
    }

    public function test_with_timing_method()
    {
        $result = ProcessingResult::withTiming(function () {
            usleep(1000); // Sleep for 1ms
            return 'test result';
        });

        $this->assertTrue($result->success);
        $this->assertEquals('test result', $result->data);
        $this->assertArrayHasKey('processing_time', $result->metadata);
        $this->assertGreaterThan(0, $result->metadata['processing_time']);
    }

    public function test_with_timing_with_exception()
    {
        $result = ProcessingResult::withTiming(function () {
            throw new \Exception('Test exception');
        });

        $this->assertFalse($result->success);
        $this->assertEquals(['Test exception'], $result->errors);
        $this->assertArrayHasKey('processing_time', $result->metadata);
    }

    public function test_batch_processing()
    {
        $results = [
            ProcessingResult::success('data1'),
            ProcessingResult::success('data2'),
            ProcessingResult::failure(['Error 1'])
        ];

        $batchResult = ProcessingResult::batch($results);

        $this->assertFalse($batchResult->success);
        $this->assertEquals([0 => 'data1', 1 => 'data2'], $batchResult->data);
        $this->assertEquals(['Error 1'], $batchResult->errors);
        $this->assertEquals(3, $batchResult->metadata['batch_count']);
    }

    public function test_conditional_processing()
    {
        $successResult = ProcessingResult::when(true, function () {
            return ProcessingResult::success('success data');
        });
        $this->assertTrue($successResult->success);
        $this->assertEquals('success data', $successResult->data);

        $failureResult = ProcessingResult::when(false, function () {
            return ProcessingResult::success('success data');
        }, function () {
            return ProcessingResult::failure(['Condition failed']);
        });
        $this->assertFalse($failureResult->success);
        $this->assertEquals(['Condition failed'], $failureResult->errors);
    }

    public function test_debug_information()
    {
        $result = ProcessingResult::success('test data', 'Success message', [
            'key' => 'value',
            'execution_time' => 1.5,
            'memory_usage' => 1024
        ]);

        $debug = $result->debug();

        // Check that debug returns an array with the correct structure
        $this->assertIsArray($debug);
        $this->assertArrayHasKey('result_info', $debug);
        $this->assertArrayHasKey('performance_info', $debug);
        $this->assertArrayHasKey('efficiency_info', $debug);
        $this->assertArrayHasKey('data_info', $debug);
        $this->assertArrayHasKey('metadata', $debug);

        // Check result_info structure
        $this->assertTrue($debug['result_info']['success']);
        $this->assertTrue($debug['result_info']['has_data']);
        $this->assertFalse($debug['result_info']['has_errors']);
        $this->assertFalse($debug['result_info']['has_warnings']);
        $this->assertEquals('Success message', $debug['result_info']['message']);

        // Check data_info structure
        $this->assertEquals('string', $debug['data_info']['data_type']);
        $this->assertEquals(0, $debug['data_info']['error_count']);
        $this->assertEquals(0, $debug['data_info']['warning_count']);
    }

    public function test_to_string_conversion()
    {
        $successResult = ProcessingResult::success();
        $this->assertEquals('SUCCESS', (string) $successResult);

        $failureResult = ProcessingResult::failure(['Error']);
        $this->assertEquals('FAILURE - 1 error(s)', (string) $failureResult);
    }

    public function test_get_formatted_result()
    {
        $result = ProcessingResult::failure(['Error 1'], 'Failed', ['Warning 1'], ['key' => 'value']);
        $formatted = $result->getFormattedResult();

        // Check basic structure
        $this->assertIsArray($formatted);
        $this->assertArrayHasKey('success', $formatted);
        $this->assertArrayHasKey('message', $formatted);
        $this->assertArrayHasKey('summary', $formatted);
        $this->assertArrayHasKey('errors', $formatted);
        $this->assertArrayHasKey('warnings', $formatted);
        $this->assertArrayHasKey('metadata', $formatted);

        // Note: 'data' is not included because there's no data
        $this->assertArrayNotHasKey('data', $formatted);

        $this->assertFalse($formatted['success']);
        $this->assertEquals('Failed', $formatted['message']);
        $this->assertEquals('FAILURE - 1 error(s) - 1 warning(s)', $formatted['summary']);
    }

    public function test_get_performance_array()
    {
        $result = ProcessingResult::success('', '', [
            'processing_duration' => 1.5,
            'processing_time' => 1.5,
            'memory_usage' => 1024,
            'affected_records' => 10,
            'affected_rows' => 10,
            'operation_type' => 'CREATE'
        ]);

        $performance = $result->getPerformance();

        $this->assertIsArray($performance);
        $this->assertArrayHasKey('processing_time', $performance);
        $this->assertArrayHasKey('memory_usage', $performance);
        $this->assertArrayHasKey('affected_rows', $performance);
        $this->assertArrayHasKey('operation_type', $performance);
        $this->assertArrayHasKey('timestamp', $performance);

        $this->assertEquals(1.5, $performance['processing_time']);
        $this->assertEquals(1024.0, $performance['memory_usage']);
        $this->assertEquals(10, $performance['affected_rows']);
        $this->assertEquals('CREATE', $performance['operation_type']);
    }
}
