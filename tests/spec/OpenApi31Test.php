<?php

use cebe\openapi\Reader;
use cebe\openapi\Writer;
use cebe\openapi\spec\Encoding;
use cebe\openapi\spec\License;
use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Schema;

/**
 * Features added in OpenAPI 3.1 that are not specific to a single spec object.
 */
class OpenApi31Test extends \PHPUnit\Framework\TestCase
{
    private function readDocument(): OpenApi
    {
        return Reader::readFromYamlFile(realpath(__DIR__ . '/data/openapi-3.1.yaml'));
    }

    public function testDocumentIsValid()
    {
        $openapi = $this->readDocument();

        $this->assertTrue($openapi->validate(), implode("\n", $openapi->getErrors()));
        $this->assertSame([], $openapi->getErrors());
    }

    public function testBooleanSchemas()
    {
        $schemas = $this->readDocument()->components->schemas;

        $this->assertTrue($schemas['Anything']);
        $this->assertFalse($schemas['Nothing']);

        $this->assertInstanceOf(Schema::class, $schemas['EmptyList']);
        $this->assertFalse($schemas['EmptyList']->items);

        $combined = $schemas['Combined'];
        $this->assertTrue($combined->properties['anything']);
        $this->assertFalse($combined->properties['nothing']);
        $this->assertSame([true], $combined->allOf);
        $this->assertFalse($combined->not);
        $this->assertFalse($combined->additionalProperties);
    }

    public function testExternalReferenceToBooleanSchema()
    {
        $combined = $this->readDocument()->components->schemas['Combined'];

        $this->assertTrue($combined->properties['external']);
        $this->assertTrue($combined->properties['externalAlias']);
    }

    public function testBooleanSchemasAreWritten()
    {
        $data = json_decode(Writer::writeToJson($this->readDocument()), true);
        $schemas = $data['components']['schemas'];

        $this->assertTrue($schemas['Anything']);
        $this->assertFalse($schemas['Nothing']);
        $this->assertFalse($schemas['EmptyList']['items']);
        $this->assertSame(['anything' => true, 'nothing' => false, 'external' => true, 'externalAlias' => true], $schemas['Combined']['properties']);
        $this->assertSame([true], $schemas['Combined']['allOf']);
        $this->assertFalse($schemas['Combined']['not']);
    }

    public function testMediaTypeWithBooleanSchemas()
    {
        $mediaType = new MediaType(['schema' => true]);
        $this->assertTrue($mediaType->schema);

        $mediaType = new MediaType([
            'schema' => ['properties' => ['file' => true]],
            'encoding' => ['file' => ['contentType' => 'text/plain']],
        ]);
        $this->assertInstanceOf(Encoding::class, $mediaType->encoding['file']);
    }

    public function testParameterDoesNotAllowBooleanSchemaAndContent()
    {
        $parameter = new Parameter(['name' => 'a', 'in' => 'query', 'schema' => true]);
        $this->assertTrue($parameter->validate(), implode("\n", $parameter->getErrors()));

        $parameter = new Parameter(['name' => 'a', 'in' => 'query', 'schema' => false, 'content' => ['text/plain' => []]]);
        $this->assertFalse($parameter->validate());
    }

    public function testFields()
    {
        $openapi = $this->readDocument();

        $this->assertSame('https://json-schema.org/draft/2020-12/schema', $openapi->jsonSchemaDialect);
        $this->assertSame('Fields and boolean schemas added in OpenAPI 3.1', $openapi->info->summary);
        $this->assertSame('Apache-2.0', $openapi->info->license->identifier);
        $this->assertInstanceOf(PathItem::class, $openapi->components->pathItems['Shared']);
        $this->assertInstanceOf(PathItem::class, $openapi->webhooks['newPet']);
        $this->assertSame('mutualTLS', $openapi->components->securitySchemes['tls']->type);
    }

    public function testFieldsAreOptional()
    {
        $openapi = new OpenApi([]);

        $this->assertNull($openapi->jsonSchemaDialect);
        $this->assertSame([], $openapi->webhooks);
    }

    public function testEmptyWebhooksAreWrittenAsMap()
    {
        $openapi = new OpenApi([
            'openapi' => '3.1.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'webhooks' => [],
        ]);

        $this->assertEquals(new stdClass(), json_decode(Writer::writeToJson($openapi))->webhooks);
    }

    public function testLicenseDoesNotAllowIdentifierAndUrl()
    {
        $license = new License(['name' => 'MIT', 'identifier' => 'MIT']);
        $this->assertTrue($license->validate(), implode("\n", $license->getErrors()));

        $license = new License(['name' => 'MIT', 'identifier' => 'MIT', 'url' => 'https://opensource.org/license/mit']);
        $this->assertFalse($license->validate());
        $this->assertSame(['A License Object MUST NOT contain both an identifier and a url.'], $license->getErrors());
    }
}
