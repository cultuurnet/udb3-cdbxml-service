<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

class CdbXmlDateFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CdbXmlDateFormatter
     */
    private $formatter;

    public function setUp()
    {
        date_default_timezone_set('Europe/Brussels');
        $this->formatter = new CdbXmlDateFormatter();
    }

    /**
     * @test
     */
    public function it_formats_a_given_timestamp()
    {
        $timestamp = 1460710907;
        $this->assertEquals(
            '2016-04-15T11:01:47',
            $this->formatter->format($timestamp)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_timestamp_is_not_an_int()
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Timestamp should be of type int, string given.'
        );

        $this->formatter->format('foo');
    }
}
