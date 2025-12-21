<?php

namespace Tests\Unit;

use App\Models\Parameter;
use App\Services\EnvManagerService;
use App\Services\SiteBuildService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SiteBuildServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SiteBuildService $service;
    protected string $testProjectPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SiteBuildService::class);
        $this->testProjectPath = storage_path('framework/testing/project_' . uniqid());

        // Create test directory
        if (!is_dir($this->testProjectPath)) {
            mkdir($this->testProjectPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test directory recursively
        if (is_dir($this->testProjectPath)) {
            $this->deleteDirectory($this->testProjectPath);
        }

        parent::tearDown();
    }

    protected function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Test Case 1: Uses .env.prod when APP_ENV_BUILD parameter is 'prod'
     */
    public function test_it_uses_env_prod_when_parameter_is_prod(): void
    {
        // Arrange
        file_put_contents("{$this->testProjectPath}/.env.prod", "APP_ENV=production\n");
        Parameter::create(['key' => 'APP_ENV_BUILD', 'value' => 'prod', 'type' => 'string', 'description' => 'Test']);

        // Act
        $sourcePath = $this->invokeProtectedMethod('determineEnvSourcePath', [$this->testProjectPath]);

        // Assert
        $this->assertStringEndsWith('.env.prod', $sourcePath);
        $this->assertFileExists($sourcePath);
    }

    /**
     * Test Case 2: Uses .env.develop when APP_ENV_BUILD parameter is 'dev'
     */
    public function test_it_uses_env_dev_when_parameter_is_dev(): void
    {
        // Arrange
        file_put_contents("{$this->testProjectPath}/.env.dev", "APP_ENV=development\n");
        Parameter::create(['key' => 'APP_ENV_BUILD', 'value' => 'dev', 'type' => 'string', 'description' => 'Test']);

        // Act
        $sourcePath = $this->invokeProtectedMethod('determineEnvSourcePath', [$this->testProjectPath]);

        // Assert
        $this->assertStringEndsWith('.env.dev', $sourcePath);
        $this->assertFileExists($sourcePath);
    }

    /**
     * Test Case 3: Falls back to .env.example when parameter is missing
     */
    public function test_it_falls_back_to_example_when_parameter_is_missing(): void
    {
        // Arrange
        file_put_contents("{$this->testProjectPath}/.env.example", "APP_ENV=local\n");
        // No parameter created

        // Act
        $sourcePath = $this->invokeProtectedMethod('determineEnvSourcePath', [$this->testProjectPath]);

        // Assert
        $this->assertStringEndsWith('.env.example', $sourcePath);
        $this->assertFileExists($sourcePath);
    }

    /**
     * Test Case 4: Falls back to .env.example when preferred source doesn't exist
     */
    public function test_it_falls_back_to_example_when_preferred_source_missing(): void
    {
        // Arrange
        file_put_contents("{$this->testProjectPath}/.env.example", "APP_ENV=local\n");
        Parameter::create(['key' => 'APP_ENV_BUILD', 'value' => 'prod', 'type' => 'string', 'description' => 'Test']);

        // Expect warning log
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Preferred source file') &&
                       str_contains($message, 'falling back to .env.example');
            });

        // Act
        $sourcePath = $this->invokeProtectedMethod('determineEnvSourcePath', [$this->testProjectPath]);

        // Assert
        $this->assertStringEndsWith('.env.example', $sourcePath);
    }

    /**
     * Test Case 5: Throws exception if no valid source exists
     */
    public function test_it_throws_exception_if_no_source_exists(): void
    {
        // Arrange
        Parameter::create(['key' => 'APP_ENV_BUILD', 'value' => 'prod', 'type' => 'string', 'description' => 'Test']);

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No valid .env source file found');

        $this->invokeProtectedMethod('determineEnvSourcePath', [$this->testProjectPath]);
    }

    /**
     * Test Case 6: Throws exception when .env.example also doesn't exist
     */
    public function test_it_throws_exception_when_example_also_missing(): void
    {
        // Arrange: No files, no parameter

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No valid .env source file found');

        $this->invokeProtectedMethod('determineEnvSourcePath', [$this->testProjectPath]);
    }



    /**
     * Test Case 9: Parameter value is case-insensitive
     */
    public function test_parameter_value_is_case_insensitive(): void
    {
        // Arrange
        file_put_contents("{$this->testProjectPath}/.env.prod", "APP_ENV=production\n");

        $testCases = ['PROD', 'Prod', 'pRoD'];
        foreach ($testCases as $value) {
            Parameter::query()->delete();
            Parameter::create(['key' => 'APP_ENV_BUILD', 'value' => $value, 'type' => 'string', 'description' => 'Test']);

            // Act
            $sourcePath = $this->invokeProtectedMethod('determineEnvSourcePath', [$this->testProjectPath]);

            // Assert
            $this->assertStringEndsWith('.env.prod', $sourcePath, "Failed for: {$value}");
        }
    }

    /**
     * Test Case 10: Empty or whitespace parameter defaults to .env.example
     */
    public function test_empty_parameter_defaults_to_example(): void
    {
        // Arrange
        file_put_contents("{$this->testProjectPath}/.env.example", "APP_ENV=local\n");

        $testValues = ['', '   '];
        foreach ($testValues as $value) {
            Parameter::query()->delete();
            Parameter::create(['key' => 'APP_ENV_BUILD', 'value' => $value, 'type' => 'string', 'description' => 'Test']);

            // Act
            $sourcePath = $this->invokeProtectedMethod('determineEnvSourcePath', [$this->testProjectPath]);

            // Assert
            $this->assertStringEndsWith('.env.example', $sourcePath);
        }
    }

    /**
     * Helper method to invoke protected methods for testing
     */
    protected function invokeProtectedMethod(string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(SiteBuildService::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->service, $parameters);
    }
}
