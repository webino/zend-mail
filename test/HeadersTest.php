<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mail;

use Zend\Mail;
use Zend\Mail\Header;

/**
 * @group      Zend_Mail
 * @covers Zend\Mail\Headers<extended>
 */
class HeadersTest extends \PHPUnit_Framework_TestCase
{
    public function testHeadersImplementsProperClasses()
    {
        $headers = new Mail\Headers();
        $this->assertInstanceOf('Iterator', $headers);
        $this->assertInstanceOf('Countable', $headers);
    }

    public function testHeadersFromStringFactoryCreatesSingleObject()
    {
        $headers = Mail\Headers::fromString("Fake: foo-bar");
        $this->assertEquals(1, $headers->count());

        $header = $headers->get('fake');
        $this->assertInstanceOf('Zend\Mail\Header\GenericHeader', $header);
        $this->assertEquals('Fake', $header->getFieldName());
        $this->assertEquals('foo-bar', $header->getFieldValue());
    }

    public function testHeadersFromStringFactoryHandlesMissingWhitespace()
    {
        $headers = Mail\Headers::fromString("Fake:foo-bar");
        $this->assertEquals(1, $headers->count());

        $header = $headers->get('fake');
        $this->assertInstanceOf('Zend\Mail\Header\GenericHeader', $header);
        $this->assertEquals('Fake', $header->getFieldName());
        $this->assertEquals('foo-bar', $header->getFieldValue());
    }

    /**
     * @group 6657
     */
    public function testHeadersFromStringFactoryCreatesSingleObjectWithContinuationLine()
    {
        $headers = Mail\Headers::fromString("Fake: foo-bar,\r\n      blah-blah");
        $this->assertEquals(1, $headers->count());

        $header = $headers->get('fake');
        $this->assertInstanceOf('Zend\Mail\Header\GenericHeader', $header);
        $this->assertEquals('Fake', $header->getFieldName());
        $this->assertEquals('foo-bar, blah-blah', $header->getFieldValue());
    }

    public function testHeadersFromStringFactoryCreatesSingleObjectWithHeaderBreakLine()
    {
        $headers = Mail\Headers::fromString("Fake: foo-bar\r\n\r\n");
        $this->assertEquals(1, $headers->count());

        $header = $headers->get('fake');
        $this->assertInstanceOf('Zend\Mail\Header\GenericHeader', $header);
        $this->assertEquals('Fake', $header->getFieldName());
        $this->assertEquals('foo-bar', $header->getFieldValue());
    }

    public function testHeadersFromStringFactoryThrowsExceptionOnMalformedHeaderLine()
    {
        $this->setExpectedException('Zend\Mail\Exception\RuntimeException', 'does not match');
        Mail\Headers::fromString("Fake = foo-bar\r\n\r\n");
    }

    public function testHeadersFromStringFactoryCreatesMultipleObjects()
    {
        $headers = Mail\Headers::fromString("Fake: foo-bar\r\nAnother-Fake: boo-baz");
        $this->assertEquals(2, $headers->count());

        $header = $headers->get('fake');
        $this->assertInstanceOf('Zend\Mail\Header\GenericHeader', $header);
        $this->assertEquals('Fake', $header->getFieldName());
        $this->assertEquals('foo-bar', $header->getFieldValue());

        $header = $headers->get('anotherfake');
        $this->assertInstanceOf('Zend\Mail\Header\GenericHeader', $header);
        $this->assertEquals('Another-Fake', $header->getFieldName());
        $this->assertEquals('boo-baz', $header->getFieldValue());
    }

    public function testHeadersFromStringMultiHeaderWillAggregateLazyLoadedHeaders()
    {
        $headers = new Mail\Headers();
        /* @var $pcl \Zend\Loader\PluginClassLoader */
        $pcl = $headers->getPluginClassLoader();
        $pcl->registerPlugin('foo', 'Zend\Mail\Header\GenericMultiHeader');
        $headers->addHeaderLine('foo: bar1,bar2,bar3');
        $headers->forceLoading();
        $this->assertEquals(3, $headers->count());
    }

    public function testHeadersHasAndGetWorkProperly()
    {
        $headers = new Mail\Headers();
        $headers->addHeaders([
            $f = new Header\GenericHeader('Foo', 'bar'),
            new Header\GenericHeader('Baz', 'baz'),
        ]);
        $this->assertFalse($headers->has('foobar'));
        $this->assertTrue($headers->has('foo'));
        $this->assertTrue($headers->has('Foo'));
        $this->assertEquals('bar', $headers->get('foo')->getFieldValue());
    }

    public function testHeadersAggregatesHeaderObjects()
    {
        $fakeHeader = new Header\GenericHeader('Fake', 'bar');
        $headers = new Mail\Headers();
        $headers->addHeader($fakeHeader);
        $this->assertEquals(1, $headers->count());
        $this->assertEquals('bar', $headers->get('Fake')->getFieldValue());
    }

    public function testHeadersAggregatesHeaderThroughAddHeader()
    {
        $headers = new Mail\Headers();
        $headers->addHeader(new Header\GenericHeader('Fake', 'bar'));
        $this->assertEquals(1, $headers->count());
        $this->assertInstanceOf('Zend\Mail\Header\GenericHeader', $headers->get('Fake'));
    }

    public function testHeadersAggregatesHeaderThroughAddHeaderLine()
    {
        $headers = new Mail\Headers();
        $headers->addHeaderLine('Fake', 'bar');
        $this->assertEquals(1, $headers->count());
        $this->assertInstanceOf('Zend\Mail\Header\GenericHeader', $headers->get('Fake'));
    }

    public function testHeadersAddHeaderLineThrowsExceptionOnMissingFieldValue()
    {
        $this->setExpectedException(
            'Zend\Mail\Header\Exception\InvalidArgumentException',
            'Header must match with the format "name:value"'
        );
        $headers = new Mail\Headers();
        $headers->addHeaderLine('Foo');
    }

    public function testHeadersAggregatesHeadersThroughAddHeaders()
    {
        $headers = new Mail\Headers();
        $headers->addHeaders([new Header\GenericHeader('Foo', 'bar'), new Header\GenericHeader('Baz', 'baz')]);
        $this->assertEquals(2, $headers->count());
        $this->assertInstanceOf('Zend\Mail\Header\GenericHeader', $headers->get('Foo'));
        $this->assertEquals('bar', $headers->get('foo')->getFieldValue());
        $this->assertEquals('baz', $headers->get('baz')->getFieldValue());

        $headers = new Mail\Headers();
        $headers->addHeaders(['Foo: bar', 'Baz: baz']);
        $this->assertEquals(2, $headers->count());
        $this->assertInstanceOf('Zend\Mail\Header\GenericHeader', $headers->get('Foo'));
        $this->assertEquals('bar', $headers->get('foo')->getFieldValue());
        $this->assertEquals('baz', $headers->get('baz')->getFieldValue());

        $headers = new Mail\Headers();
        $headers->addHeaders([['Foo' => 'bar'], ['Baz' => 'baz']]);
        $this->assertEquals(2, $headers->count());
        $this->assertInstanceOf('Zend\Mail\Header\GenericHeader', $headers->get('Foo'));
        $this->assertEquals('bar', $headers->get('foo')->getFieldValue());
        $this->assertEquals('baz', $headers->get('baz')->getFieldValue());

        $headers = new Mail\Headers();
        $headers->addHeaders([['Foo', 'bar'], ['Baz', 'baz']]);
        $this->assertEquals(2, $headers->count());
        $this->assertInstanceOf('Zend\Mail\Header\GenericHeader', $headers->get('Foo'));
        $this->assertEquals('bar', $headers->get('foo')->getFieldValue());
        $this->assertEquals('baz', $headers->get('baz')->getFieldValue());

        $headers = new Mail\Headers();
        $headers->addHeaders(['Foo' => 'bar', 'Baz' => 'baz']);
        $this->assertEquals(2, $headers->count());
        $this->assertInstanceOf('Zend\Mail\Header\GenericHeader', $headers->get('Foo'));
        $this->assertEquals('bar', $headers->get('foo')->getFieldValue());
        $this->assertEquals('baz', $headers->get('baz')->getFieldValue());
    }

    public function testHeadersAddHeadersThrowsExceptionOnInvalidArguments()
    {
        $this->setExpectedException('Zend\Mail\Exception\InvalidArgumentException', 'Expected array or Trav');
        $headers = new Mail\Headers();
        $headers->addHeaders('foo');
    }

    public function testHeadersCanRemoveHeader()
    {
        $headers = new Mail\Headers();
        $headers->addHeaders(['Foo' => 'bar', 'Baz' => 'baz']);
        $this->assertEquals(2, $headers->count());
        $headers->removeHeader('foo');
        $this->assertEquals(1, $headers->count());
        $this->assertFalse($headers->has('foo'));
        $this->assertTrue($headers->has('baz'));
    }

    public function testRemoveHeaderWithFieldNameWillRemoveAllInstances()
    {
        $headers = new Mail\Headers();
        $headers->addHeaders([['Foo' => 'foo'], ['Foo' => 'bar'], 'Baz' => 'baz']);
        $this->assertEquals(3, $headers->count());
        $headers->removeHeader('foo');
        $this->assertEquals(1, $headers->count());
        $this->assertFalse($headers->get('foo'));
        $this->assertTrue($headers->has('baz'));
    }

    public function testRemoveHeaderWithInstanceWillRemoveThatInstance()
    {
        $headers = new Mail\Headers();
        $headers->addHeaders([['Foo' => 'foo'], ['Foo' => 'bar'], 'Baz' => 'baz']);
        $header = $headers->get('foo')->current();
        $this->assertEquals(3, $headers->count());
        $headers->removeHeader($header);
        $this->assertEquals(2, $headers->count());
        $this->assertTrue($headers->has('foo'));
        $this->assertNotSame($header, $headers->get('foo'));
    }

    public function testHeadersCanClearAllHeaders()
    {
        $headers = new Mail\Headers();
        $headers->addHeaders(['Foo' => 'bar', 'Baz' => 'baz']);
        $this->assertEquals(2, $headers->count());
        $headers->clearHeaders();
        $this->assertEquals(0, $headers->count());
    }

    public function testHeadersCanBeIterated()
    {
        $headers = new Mail\Headers();
        $headers->addHeaders(['Foo' => 'bar', 'Baz' => 'baz']);
        $iterations = 0;
        foreach ($headers as $index => $header) {
            $iterations++;
            $this->assertInstanceOf('Zend\Mail\Header\GenericHeader', $header);
            switch ($index) {
                case 0:
                    $this->assertEquals('bar', $header->getFieldValue());
                    break;
                case 1:
                    $this->assertEquals('baz', $header->getFieldValue());
                    break;
                default:
                    $this->fail('Invalid index returned from iterator');
            }
        }
        $this->assertEquals(2, $iterations);
    }

    public function testHeadersCanBeCastToString()
    {
        $headers = new Mail\Headers();
        $headers->addHeaders(['Foo' => 'bar', 'Baz' => 'baz']);
        $this->assertEquals('Foo: bar' . "\r\n" . 'Baz: baz' . "\r\n", $headers->toString());
    }

    public function testHeadersCanBeCastToArray()
    {
        $headers = new Mail\Headers();
        $headers->addHeaders(['Foo' => 'bar', 'Baz' => 'baz']);
        $this->assertEquals(['Foo' => 'bar', 'Baz' => 'baz'], $headers->toArray());
    }

    public function testCastingToArrayReturnsMultiHeadersAsArrays()
    {
        $headers = new Mail\Headers();
        $received1 = Header\Received::fromString("Received: from framework (localhost [127.0.0.1])\r\n by framework (Postfix) with ESMTP id BBBBBBBBBBB\r\n for <zend@framework>; Mon, 21 Nov 2011 12:50:27 -0600 (CST)");
        $received2 = Header\Received::fromString("Received: from framework (localhost [127.0.0.1])\r\n by framework (Postfix) with ESMTP id AAAAAAAAAAA\r\n for <zend@framework>; Mon, 21 Nov 2011 12:50:29 -0600 (CST)");
        $headers->addHeader($received1);
        $headers->addHeader($received2);
        $array   = $headers->toArray();
        $expected = [
            'Received' => [
                $received1->getFieldValue(),
                $received2->getFieldValue(),
            ],
        ];
        $this->assertEquals($expected, $array);
    }

    public function testCastingToStringReturnsAllMultiHeaderValues()
    {
        $headers = new Mail\Headers();
        $received1 = Header\Received::fromString("Received: from framework (localhost [127.0.0.1])\r\n by framework (Postfix) with ESMTP id BBBBBBBBBBB\r\n for <zend@framework>; Mon, 21 Nov 2011 12:50:27 -0600 (CST)");
        $received2 = Header\Received::fromString("Received: from framework (localhost [127.0.0.1])\r\n by framework (Postfix) with ESMTP id AAAAAAAAAAA\r\n for <zend@framework>; Mon, 21 Nov 2011 12:50:29 -0600 (CST)");
        $headers->addHeader($received1);
        $headers->addHeader($received2);
        $string  = $headers->toString();
        $expected = [
            'Received: ' . $received1->getFieldValue(),
            'Received: ' . $received2->getFieldValue(),
        ];
        $expected = implode("\r\n", $expected) . "\r\n";
        $this->assertEquals($expected, $string);
    }

    /**
     * @test that toArray can take format parameter
     * @link https://github.com/zendframework/zend-mail/pull/61
     */
    public function testToArrayFormatRaw()
    {
        $raw_subject = '=?ISO-8859-2?Q?PD=3A_My=3A_Go=B3?= =?ISO-8859-2?Q?blahblah?=';
        $headers = new Mail\Headers();
        $subject = Header\Subject::fromString("Subject: $raw_subject");
        $headers->addHeader($subject);
        // default
        $array = $headers->toArray(Header\HeaderInterface::FORMAT_RAW);
        $expected = [
            'Subject' => 'PD: My: Gołblahblah',
        ];
        $this->assertEquals($expected, $array);
    }

    /**
     * @test that toArray can take format parameter
     * @link https://github.com/zendframework/zend-mail/pull/61
     */
    public function testToArrayFormatEncoded()
    {
        $raw_subject = '=?ISO-8859-2?Q?PD=3A_My=3A_Go=B3?= =?ISO-8859-2?Q?blahblah?=';
        $headers = new Mail\Headers();
        $subject = Header\Subject::fromString("Subject: $raw_subject");
        $headers->addHeader($subject);

        // encoded
        $array = $headers->toArray(Header\HeaderInterface::FORMAT_ENCODED);
        $expected = [
            'Subject' => '=?UTF-8?Q?PD:=20My:=20Go=C5=82blahblah?=',
        ];
        $this->assertEquals($expected, $array);
    }

    public static function expectedHeaders()
    {
        return [
            ['bcc', 'Zend\Mail\Header\Bcc'],
            ['cc', 'Zend\Mail\Header\Cc'],
            ['contenttype', 'Zend\Mail\Header\ContentType'],
            ['content_type', 'Zend\Mail\Header\ContentType'],
            ['content-type', 'Zend\Mail\Header\ContentType'],
            ['date', 'Zend\Mail\Header\Date'],
            ['from', 'Zend\Mail\Header\From'],
            ['mimeversion', 'Zend\Mail\Header\MimeVersion'],
            ['mime_version', 'Zend\Mail\Header\MimeVersion'],
            ['mime-version', 'Zend\Mail\Header\MimeVersion'],
            ['received', 'Zend\Mail\Header\Received'],
            ['replyto', 'Zend\Mail\Header\ReplyTo'],
            ['reply_to', 'Zend\Mail\Header\ReplyTo'],
            ['reply-to', 'Zend\Mail\Header\ReplyTo'],
            ['sender', 'Zend\Mail\Header\Sender'],
            ['subject', 'Zend\Mail\Header\Subject'],
            ['to', 'Zend\Mail\Header\To'],
        ];
    }

    /**
     * @dataProvider expectedHeaders
     */
    public function testDefaultPluginLoaderIsSeededWithHeaders($plugin, $class)
    {
        $headers = new Mail\Headers();
        $loader  = $headers->getPluginClassLoader();
        $test    = $loader->load($plugin);
        $this->assertEquals($class, $test);
    }

    public function testClone()
    {
        $headers = new Mail\Headers();
        $headers->addHeader(new Header\Bcc());
        $headers2 = clone($headers);
        $this->assertEquals($headers, $headers2);
        $headers2->removeHeader('Bcc');
        $this->assertTrue($headers->has('Bcc'));
        $this->assertFalse($headers2->has('Bcc'));
    }

    /**
     * @group ZF2015-04
     */
    public function testHeaderCrLfAttackFromString()
    {
        $this->setExpectedException('Zend\Mail\Exception\RuntimeException');
        Mail\Headers::fromString("Fake: foo-bar\r\n\r\nevilContent");
    }

    /**
     * @group ZF2015-04
     */
    public function testHeaderCrLfAttackAddHeaderLineSingle()
    {
        $headers = new Mail\Headers();
        $this->setExpectedException('Zend\Mail\Header\Exception\InvalidArgumentException');
        $headers->addHeaderLine("Fake: foo-bar\r\n\r\nevilContent");
    }

    /**
     * @group ZF2015-04
     */
    public function testHeaderCrLfAttackAddHeaderLineWithValue()
    {
        $headers = new Mail\Headers();
        $this->setExpectedException('Zend\Mail\Header\Exception\InvalidArgumentException');
        $headers->addHeaderLine('Fake', "foo-bar\r\n\r\nevilContent");
    }

    /**
     * @group ZF2015-04
     */
    public function testHeaderCrLfAttackAddHeaderLineMultiple()
    {
        $headers = new Mail\Headers();
        $this->setExpectedException('Zend\Mail\Header\Exception\InvalidArgumentException');
        $headers->addHeaderLine('Fake', ["foo-bar\r\n\r\nevilContent"]);
        $headers->forceLoading();
    }

    /**
     * @group ZF2015-04
     */
    public function testHeaderCrLfAttackAddHeadersSingle()
    {
        $headers = new Mail\Headers();
        $this->setExpectedException('Zend\Mail\Header\Exception\InvalidArgumentException');
        $headers->addHeaders(["Fake: foo-bar\r\n\r\nevilContent"]);
    }

    /**
     * @group ZF2015-04
     */
    public function testHeaderCrLfAttackAddHeadersWithValue()
    {
        $headers = new Mail\Headers();
        $this->setExpectedException('Zend\Mail\Header\Exception\InvalidArgumentException');
        $headers->addHeaders(['Fake' => "foo-bar\r\n\r\nevilContent"]);
    }

    /**
     * @group ZF2015-04
     */
    public function testHeaderCrLfAttackAddHeadersMultiple()
    {
        $headers = new Mail\Headers();
        $this->setExpectedException('Zend\Mail\Header\Exception\InvalidArgumentException');
        $headers->addHeaders(['Fake' => ["foo-bar\r\n\r\nevilContent"]]);
        $headers->forceLoading();
    }
}
