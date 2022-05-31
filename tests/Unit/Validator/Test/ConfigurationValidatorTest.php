<?php

declare(strict_types=1);

namespace webignition\BasilLoader\Tests\Unit\Validator\Test;

use webignition\BasilLoader\Validator\InvalidResult;
use webignition\BasilLoader\Validator\InvalidResultInterface;
use webignition\BasilLoader\Validator\ResultType;
use webignition\BasilLoader\Validator\Test\ConfigurationValidator;
use webignition\BasilLoader\Validator\ValidResult;
use webignition\BasilModels\Model\Test\Configuration;
use webignition\BasilModels\Model\Test\ConfigurationInterface;

class ConfigurationValidatorTest extends \PHPUnit\Framework\TestCase
{
    private ConfigurationValidator $configurationValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurationValidator = ConfigurationValidator::create();
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(
        ConfigurationInterface $configuration,
        InvalidResultInterface $expectedResult
    ): void {
        $this->assertEquals($expectedResult, $this->configurationValidator->validate($configuration));
    }

    /**
     * @return array<mixed>
     */
    public function validateNotValidDataProvider(): array
    {
        return [
            'browser empty' => [
                'configuration' => new Configuration('', ''),
                'expectedResult' => new InvalidResult(
                    new Configuration('', ''),
                    ResultType::TEST_CONFIGURATION,
                    ConfigurationValidator::REASON_BROWSER_EMPTY
                ),
            ],
            'browser whitespace-only' => [
                'configuration' => new Configuration('   ', ''),
                'expectedResult' => new InvalidResult(
                    new Configuration('   ', ''),
                    ResultType::TEST_CONFIGURATION,
                    ConfigurationValidator::REASON_BROWSER_EMPTY
                ),
            ],
            'url empty' => [
                'configuration' => new Configuration('chrome', ''),
                'expectedResult' => new InvalidResult(
                    new Configuration('chrome', ''),
                    ResultType::TEST_CONFIGURATION,
                    ConfigurationValidator::REASON_URL_EMPTY
                ),
            ],
            'url is page url reference' => [
                'configuration' => new Configuration('chrome', '$page_import_name.url'),
                'expectedResult' => new InvalidResult(
                    new Configuration('chrome', '$page_import_name.url'),
                    ResultType::TEST_CONFIGURATION,
                    ConfigurationValidator::REASON_URL_IS_PAGE_URL_REFERENCE
                ),
            ],
        ];
    }

    public function testValidateIsValid(): void
    {
        $configuration = new Configuration('chrome', 'http://example.com/');

        $expectedResult = new ValidResult($configuration);

        $this->assertEquals($expectedResult, $this->configurationValidator->validate($configuration));
    }
}
