<?php

namespace ADWS\QRPayment\Test\TestCase;

use ADWS\QRPayment\QR;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Class QRTest.
 */
class QRTest extends TestCase
{
    public function testAccountHigherThanMaxInt()
    {
        $string = QR::accountToIban('2501301193/2010');

        $this->assertSame(
            'CZ3620100000002501301193',
            $string
        );
    }

    public function testFakeCurrencyString()
    {
        $this->expectException(InvalidArgumentException::class);

        QR::create('12-3456789012/0100', '1234.56', '2016001234')
            ->setMessage('Düakrítičs')
            ->setCurrency('FAKE');
    }

    public function testCzkString()
    {
        $string = QR::create('12-3456789012/0100', '1234.56', '2016001234')
            ->setMessage('Düakrítičs');

        $this->assertSame(
            'SPD*1.0*ACC:CZ0301000000123456789012*AM:1234.56*CC:CZK*MSG:Duakritics*X-VS:2016001234',
            $string->__toString()
        );

        $string = QR::create('12-3456789012/0100', '1234.56', '2016001234')
            ->setMessage('Düakrítičs')
            ->setCurrency('CZK');

        $this->assertSame(
            'SPD*1.0*ACC:CZ0301000000123456789012*AM:1234.56*CC:CZK*MSG:Duakritics*X-VS:2016001234',
            $string->__toString()
        );
    }

    public function testEurString()
    {
        $string = QR::create('12-3456789012/0100', '1234.56', '2016001234')
            ->setMessage('Düakrítičs')
            ->setCurrency('EUR');

        $this->assertSame(
            'SPD*1.0*ACC:CZ0301000000123456789012*AM:1234.56*CC:EUR*MSG:Duakritics*X-VS:2016001234',
            $string->__toString()
        );
    }

    public function testQrCodeInstante()
    {
        $QRPayment = QR::create('12-3456789012/0100', 987.60)
            ->setMessage('QR platba je parádní!')
            ->getQRCodeInstance();

        $this->assertInstanceOf('Endroid\\QrCode\\QrCode', $QRPayment);
    }

    public function testQrCodeBase64Instante()
    {
        $QRPayment = QR::create('12-3456789012/0100', 987.60)
            ->setMessage('QR platba musí fungovat i jako HTML!')
            ->getQRCodeImage(false);

        $this->assertStringStartsWith('data:image/png;base64,', $QRPayment);
    }

    public function testQrCodeHTMLImageInstante()
    {
        $QRPayment = QR::create('12-3456789012/0100', 987.60)
            ->setMessage('QR platba musí fungovat i jako HTML!')
            ->getQRCodeImage();

        $this->assertNotEmpty($QRPayment);
    }

    public function testQrCodePngFileIsCreated()
    {
        $temp_name = tempnam(sys_get_temp_dir(), 'QrCode');

        $this->assertTrue(is_file($temp_name), 'Could not create temp file.');
        $this->assertEmpty(file_get_contents($temp_name), 'Temp file is not empty.');

        (new QR())->setAccount('12-3456789012/0100')
            ->setVariableSymbol('2016001234')
            ->setMessage('Toto je testovací QR platba.')
            ->setSpecificSymbol('0308')
            ->setCurrency('CZK')
            ->setDueDate(new \DateTime())
            ->saveQRCodeImage($temp_name, 'png', 100, 5);

        $this->assertNotEmpty(
            file_get_contents($temp_name . '.png'),
            'QR code image for payment could not be created into the temp dir.'
        );
    }

    public function testQrCodeSvgFileIsCreated()
    {
        $temp_name = tempnam(sys_get_temp_dir(), 'QrCode');

        $this->assertTrue(is_file($temp_name), 'Could not create temp file.');
        $this->assertEmpty(file_get_contents($temp_name), 'Temp file is not empty.');

        (new QR())->setAccount('12-3456789012/0100')
            ->setVariableSymbol('2016001234')
            ->setMessage('Toto je testovací QR platba.')
            ->setSpecificSymbol('0308')
            ->setCurrency('CZK')
            ->setDueDate(new \DateTime())
            ->saveQRCodeImage($temp_name, 'svg', 300, 20);

        $this->assertNotEmpty(
            file_get_contents($temp_name . '.svg'),
            'QR code image for payment could not be created into the temp dir.'
        );
    }
}
