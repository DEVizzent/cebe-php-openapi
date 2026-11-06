<?php


use cebe\openapi\ReferenceContext;
use cebe\openapi\spec\OpenApi;

class ReferenceContextTest extends \PHPUnit\Framework\TestCase
{
    public static function resolveUriProvider()
    {
        $data = [
            [
                'https://example.com/openapi.yaml', // base URI
                'definitions.yaml', // referenced URI
                'https://example.com/definitions.yaml', // expected result
            ],
            [
                'https://example.com/openapi.yaml', // base URI
                'definitions.yaml#/components/Pet', // referenced URI
                'https://example.com/definitions.yaml#/components/Pet', // expected result
            ],

            [
                'https://example.com/openapi.yaml', // base URI
                '/definitions.yaml', // referenced URI
                'https://example.com/definitions.yaml', // expected result
            ],
            [
                'https://example.com/openapi.yaml', // base URI
                '/definitions.yaml#/components/Pet', // referenced URI
                'https://example.com/definitions.yaml#/components/Pet', // expected result
            ],

            [
                'https://example.com/api/openapi.yaml', // base URI
                'definitions.yaml', // referenced URI
                'https://example.com/api/definitions.yaml', // expected result
            ],
            [
                'https://example.com/api/openapi.yaml', // base URI
                'definitions.yaml#/components/Pet', // referenced URI
                'https://example.com/api/definitions.yaml#/components/Pet', // expected result
            ],

            [
                'https://example.com/api/openapi.yaml', // base URI
                '/definitions.yaml', // referenced URI
                'https://example.com/definitions.yaml', // expected result
            ],
            [
                'https://example.com/api/openapi.yaml', // base URI
                '/definitions.yaml#/components/Pet', // referenced URI
                'https://example.com/definitions.yaml#/components/Pet', // expected result
            ],

            [
                'https://example.com/api/openapi.yaml', // base URI
                '../definitions.yaml', // referenced URI
                'https://example.com/definitions.yaml', // expected result
            ],
            [
                'https://example.com/api/openapi.yaml', // base URI
                '../definitions.yaml#/components/Pet', // referenced URI
                'https://example.com/definitions.yaml#/components/Pet', // expected result
            ],

            [
                '/var/www/openapi.yaml', // base URI
                'definitions.yaml', // referenced URI
                'file:///var/www/definitions.yaml', // expected result
            ],
            [
                '/var/www/openapi.yaml', // base URI
                'definitions.yaml#/components/Pet', // referenced URI
                'file:///var/www/definitions.yaml#/components/Pet', // expected result
            ],

            [
                '/var/www/openapi.yaml', // base URI
                '/var/definitions.yaml', // referenced URI
                'file:///var/definitions.yaml', // expected result
            ],
            [
                '/var/www/openapi.yaml', // base URI
                '/var/definitions.yaml#/components/Pet', // referenced URI
                'file:///var/definitions.yaml#/components/Pet', // expected result
            ],
        ];

        // absolute URLs should not be changed
        foreach(array_unique(array_map('current', $data)) as $url) {
            $data[] = [
                $url,
                'file:///var/www/definitions.yaml',
                'file:///var/www/definitions.yaml',
            ];
            $data[] = [
                $url,
                'file:///var/www/definitions.yaml#/components/Pet',
                'file:///var/www/definitions.yaml#/components/Pet',
            ];

            $data[] = [
                $url,
                'https://example.com/definitions.yaml',
                'https://example.com/definitions.yaml',
            ];
            $data[] = [
                $url,
                'https://example.com/definitions.yaml#/components/Pet',
                'https://example.com/definitions.yaml#/components/Pet',
            ];
        }

        return $data;
    }

    /**
     * @dataProvider resolveUriProvider
     */
    public function testResolveUri($baseUri, $referencedUri, $expected)
    {
        $context = new ReferenceContext(new OpenApi([]), $baseUri);
        $this->assertEquals($expected, $context->resolveRelativeUri($referencedUri));
    }

    public static function normalizeUriProvider()
    {
        $data = [
            [
                'https://example.com/openapi.yaml',
                'https://example.com/openapi.yaml',
            ],
            [
                'https://example.com/openapi.yaml#/components/Pet',
                'https://example.com/openapi.yaml#/components/Pet',
            ],
            [
                'https://example.com/./openapi.yaml',
                'https://example.com/openapi.yaml',
            ],
            [
                'https://example.com/./openapi.yaml#/components/Pet',
                'https://example.com/openapi.yaml#/components/Pet',
            ],
            [
                'https://example.com/api/../openapi.yaml',
                'https://example.com/openapi.yaml',
            ],
            [
                'https://example.com/api/../openapi.yaml#/components/Pet',
                'https://example.com/openapi.yaml#/components/Pet',
            ],
            [
                'https://example.com/../openapi.yaml',
                'https://example.com/../openapi.yaml',
            ],
            [
                'https://example.com/../openapi.yaml#/components/Pet',
                'https://example.com/../openapi.yaml#/components/Pet',
            ],
            [
                '/definitions.yaml',
                'file:///definitions.yaml',
            ],
            [
                '/definitions.yaml#/components/Pet',
                'file:///definitions.yaml#/components/Pet',
            ],
            [
                '/var/www/definitions.yaml',
                'file:///var/www/definitions.yaml',
            ],
            [
                '/var/www/definitions.yaml#/components/Pet',
                'file:///var/www/definitions.yaml#/components/Pet',
            ],
            [
                '/var/www/api/../definitions.yaml',
                'file:///var/www/definitions.yaml',
            ],
            [
                '/var/www/api/../definitions.yaml#/components/Pet',
                'file:///var/www/definitions.yaml#/components/Pet',
            ],
            [
                '/var/www/api/foo/../../definitions.yaml#/components/Pet',
                'file:///var/www/definitions.yaml#/components/Pet',
            ],
        ];

        return $data;
    }

    /**
     * @dataProvider normalizeUriProvider
     */
    public function testNormalizeUri($uri, $expected)
    {
        $context = new ReferenceContext(null, $uri);
        $this->assertEquals($expected, $context->getUri());
    }

    private const DATA_URI = 'data://text/plain;base64,ZGVzY3JpcHRpb246IGZyb20td3JhcHBlcgo=';

    public static function disallowedUriProvider()
    {
        return [
            ['file:///var/www/openapi.yaml', self::DATA_URI],
            ['file:///var/www/openapi.yaml', 'php://filter/resource=/etc/passwd'],
            ['file:///var/www/openapi.yaml', 'PHP://filter/resource=/etc/passwd'],
            ['file:///var/www/openapi.yaml', 'phar:///var/www/archive.phar/definitions.yaml'],
            ['file:///var/www/openapi.yaml', 'ftp://example.invalid/definitions.yaml'],
            ['https://example.invalid/openapi.yaml', 'file:///etc/passwd'],
            ['http://example.invalid/openapi.yaml', 'file:///etc/passwd'],
        ];
    }

    /**
     * @dataProvider disallowedUriProvider
     */
    public function testFetchReferencedFileRejectsDisallowedUri($baseUri, $uri)
    {
        $context = new ReferenceContext(null, $baseUri);

        $this->expectException(\cebe\openapi\exceptions\IOException::class);
        $this->expectExceptionMessage('not allowed');
        $context->fetchReferencedFile($uri);
    }

    public function testFetchReferencedFileAllowsLocalFilesFromLocalDocument()
    {
        $file = $this->createTemporaryFile("description: from-file\n");
        try {
            $context = new ReferenceContext(null, $file);
            $this->assertSame(['description' => 'from-file'], $context->fetchReferencedFile($context->getUri()));
        } finally {
            unlink($file);
        }
    }

    public function testFetchReferencedFileAllowsSchemeOfBaseDocument()
    {
        $context = new ReferenceContext(null, 'data://text/plain;base64,e30=');

        $this->assertSame(['description' => 'from-wrapper'], $context->fetchReferencedFile(self::DATA_URI));
    }

    public function testAllowedSchemesCanBeExtended()
    {
        $context = new ReferenceContext(null, 'file:///var/www/openapi.yaml');
        $context->allowedSchemes = ['DATA'];

        $this->assertSame(['description' => 'from-wrapper'], $context->fetchReferencedFile(self::DATA_URI));
    }

    public function testAllowedSchemesCanBeRestricted()
    {
        $file = $this->createTemporaryFile("description: from-file\n");
        try {
            $context = new ReferenceContext(null, $file);
            $context->allowedSchemes = ['https'];

            $this->expectException(\cebe\openapi\exceptions\IOException::class);
            $this->expectExceptionMessage('not allowed');
            $context->fetchReferencedFile($context->getUri());
        } finally {
            unlink($file);
        }
    }

    public function testUriFilterCanRejectUri()
    {
        $file = $this->createTemporaryFile("description: from-file\n");
        try {
            $context = new ReferenceContext(null, $file);
            $seen = [];
            $context->uriFilter = function ($uri) use (&$seen) {
                $seen[] = $uri;
                return false;
            };

            try {
                $context->fetchReferencedFile($context->getUri());
                $this->fail('Expected the URI to be rejected.');
            } catch (\cebe\openapi\exceptions\IOException $e) {
                $this->assertNotFalse(strpos($e->getMessage(), 'not allowed'));
            }
            $this->assertSame([$context->getUri()], $seen);
        } finally {
            unlink($file);
        }
    }

    public function testDisallowedReferenceIsNotResolved()
    {
        $openapi = $this->createSpecWithReference(self::DATA_URI);

        $this->expectException(\cebe\openapi\exceptions\UnresolvableReferenceException::class);
        $this->expectExceptionMessage('not allowed');
        $openapi->resolveReferences(new ReferenceContext($openapi, 'file:///var/www/openapi.yaml'));
    }

    public function testDisallowedReferenceIsReportedWithoutException()
    {
        $openapi = $this->createSpecWithReference(self::DATA_URI);
        $context = new ReferenceContext($openapi, 'file:///var/www/openapi.yaml');
        $context->throwException = false;

        $openapi->resolveReferences($context);

        $reference = $openapi->components->schemas['Pet'];
        $this->assertInstanceOf(\cebe\openapi\spec\Reference::class, $reference);
        $this->assertNotFalse(strpos(implode("\n", $reference->getErrors()), 'not allowed'));
    }

    private function createSpecWithReference(string $uri): OpenApi
    {
        return new OpenApi([
            'openapi' => '3.0.3',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => [],
            'components' => ['schemas' => ['Pet' => ['$ref' => $uri]]],
        ]);
    }

    private function createTemporaryFile(string $content): string
    {
        $file = tempnam(sys_get_temp_dir(), 'openapi');
        file_put_contents($file, $content);
        return $file;
    }
}
