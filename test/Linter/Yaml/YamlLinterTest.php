<?php

namespace GrumPHPTest\Linter\Yaml;

use GrumPHP\Collection\LintErrorsCollection;
use GrumPHP\Linter\Yaml\YamlLinter;
use GrumPHP\Linter\Yaml\YamlLintError;
use GrumPHP\Util\Filesystem;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SplFileInfo;

class YamlLinterTest extends TestCase
{
    /**
     * @var YamlLinter
     */
    protected $linter;

    protected function setUp()
    {
        $this->linter = new YamlLinter(
            new Filesystem()
        );
    }

    /**
     * @param string $fixture
     *
     * @return SplFileInfo
     */
    private function getFixture($fixture)
    {
        $file = new SplFileInfo(TEST_BASE_PATH . '/fixtures/linters/yaml/' . $fixture);
        if (!$file->isReadable()) {
            throw new RuntimeException(sprintf('The fixture %s could not be loaded!', $fixture));
        }

        return $file;
    }
    /**
     * @param string $fixture
     * @param int $errors
     */
    private function validateFixture($fixture, $errors)
    {
        $result = $this->linter->lint($this->getFixture($fixture));
        $this->assertInstanceOf(LintErrorsCollection::class, $result);
        $this->assertEquals($result->count(), $errors, 'Invalid error-count expected.');
        if ($result->count()) {
            $this->assertInstanceOf(YamlLintError::class, $result[0]);
        }
    }

    /**
     * @test
     * @dataProvider provideYamlValidation
     */
    function it_should_validate_yaml_for_syntax_errors($fixture, $errors)
    {
        $this->validateFixture($fixture, $errors);
    }

    /**
     * @test
     */
    function it_should_be_able_to_handle_object_support_with_yaml_legacy()
    {
        if ($this->hasSymfonyYamlVersion('3.1', '>=')) {
            $this->markTestSkipped('This test requires Symfony YAML < 3.1');
        }

        $this->linter->setObjectSupport(true);
        $this->validateFixture('object-support-old.yml', 0);
    }

    /**
     * @test
     */
    function it_should_be_able_to_handle_object_support_with_yaml_lts()
    {
        if (!(
            $this->hasSymfonyYamlVersion('3.1', '>=') &&
            $this->hasSymfonyYamlVersion('4.0', '<')
        )) {
            $this->markTestSkipped('This test requires Symfony YAML between > 3.1 and < 4.0');
        }

        $this->linter->setObjectSupport(true);
        $this->validateFixture('object-support-with-colon.yml', 0);
    }

    /**
     * @test
     */
    function it_should_be_able_to_handle_object_support_with_yaml_current()
    {
        if ($this->hasSymfonyYamlVersion('4.0', '<')) {
            $this->markTestSkipped('This test requires Symfony YAML between > 4.0');
        }

        $this->linter->setObjectSupport(true);
        $this->validateFixture('object-support.yml', 0);
    }

    /**
     * @test
     */
    function it_should_handle_exceptions_on_invalid_type_with_yaml_legacy()
    {
        if ($this->hasSymfonyYamlVersion('3.1', '>=')) {
            $this->markTestSkipped('This test requires Symfony YAML < 3.1');
        }

        $this->linter->setObjectSupport(false);
        $this->linter->setExceptionOnInvalidType(true);

        $this->validateFixture('object-support-old.yml', 1);
    }

    /**
     * @test
     */
    function it_should_handle_exceptions_on_invalid_type_with_yaml_lts()
    {
        if (!(
            $this->hasSymfonyYamlVersion('3.1', '>=') &&
            $this->hasSymfonyYamlVersion('4.0', '<')
        )) {
            $this->markTestSkipped('This test requires Symfony YAML between > 3.1 and < 4.0');
        }

        $this->linter->setObjectSupport(false);
        $this->linter->setExceptionOnInvalidType(true);

        $this->validateFixture('object-support-with-colon.yml', 1);
    }

    /**
     * @test
     */
    function it_should_handle_exceptions_on_invalid_type_with_yaml_current()
    {
        if ($this->hasSymfonyYamlVersion('4.0', '<')) {
            $this->markTestSkipped('This test requires Symfony YAML between > 4.0');
        }

        $this->linter->setObjectSupport(false);
        $this->linter->setExceptionOnInvalidType(true);

        $this->validateFixture('object-support.yml', 1);
    }

    /**
     * @return array
     */
    function provideYamlValidation()
    {
        return [
            ['fixture' => 'valid.yml', 'errors' => 0],
            ['fixture' => 'invalid.yml', 'errors' => 1],
        ];
    }

    private function hasSymfonyYamlVersion($version, $operator = null)
    {
        $installedVersion = '2.8';

        if (YamlLinter::supportsFlags()) {
            $installedVersion = '3.1';
        }

        if (YamlLinter::supportsTagsWithoutColon()) {
            $installedVersion = '4.0';
        }

        return version_compare($installedVersion, $version, $operator);
    }
}
