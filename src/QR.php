<?php

namespace ADWS\QRPayment;

use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Margin\Margin;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\QrCodeInterface;
use Endroid\QrCode\Writer\WebPWriter;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\EpsWriter;
use Endroid\QrCode\Writer\PdfWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\BinaryWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;
use Exception;
use InvalidArgumentException;

/**
 * Library for generating QR payments in PHP.
 *
 * @see https://raw.githubusercontent.com/snoblucha/QRPlatba/master/QRPlatba.php
 */
class QR
{
    /**
     * QR version of QR Payments format.
     */
    public const SPD_VERSION = '1.0';

    /**
     * @var string[]
     */
    private static array $currencies = [
        'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN',
        'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL',
        'BSD', 'BTN', 'BWP', 'BYN', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP', 'CNY',
        'COP', 'CRC', 'CUC', 'CUP', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD',
        'EGP', 'ERN', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GGP', 'GHS',
        'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF',
        'IDR', 'ILS', 'IMP', 'INR', 'IQD', 'IRR', 'ISK', 'JEP', 'JMD', 'JOD',
        'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KPW', 'KRW', 'KWD', 'KYD', 'KZT',
        'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'LYD', 'MAD', 'MDL', 'MGA', 'MKD',
        'MMK', 'MNT', 'MOP', 'MRO', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN',
        'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK',
        'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR',
        'SBD', 'SCR', 'SDG', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SPL', 'SRD',
        'STD', 'SVC', 'SYP', 'SZL', 'THB', 'TJS', 'TMT', 'TND', 'TOP', 'TRY',
        'TTD', 'TVD', 'TWD', 'TZS', 'UAH', 'UGX', 'USD', 'UYU', 'UZS', 'VEF',
        'VND', 'VUV', 'WST', 'XAF', 'XCD', 'XDR', 'XOF', 'XPF', 'YER', 'ZAR',
        'ZMW', 'ZWD',
    ];

    /**
     * @var array<string, string|null> QR keys Payments
     */
    private array $spd_keys = [
        'ACC' => null,
        // Max. 46 - znaků IBAN, BIC Identifikace protistrany !povinny
        'ALT-ACC' => null,
        // Max. 93 - znaků Seznam alternativnich uctu. odddeleny carkou,
        'AM' => null,
        // Max. 10 znaků - Desetinné číslo Výše částky platby.
        'CC' => 'CZK',
        // Právě 3 znaky - Měna platby.
        'DT' => null,
        // Právě 8 znaků - Datum splatnosti YYYYMMDD.
        'MSG' => null,
        // Max. 60 znaků - Zpráva pro příjemce.
        'X-VS' => null,
        // Max. 10 znaků - Celé číslo - Variabilní symbol
        'X-SS' => null,
        // Max. 10 znaků - Celé číslo - Specifický symbol
        'X-KS' => null,
        // Max. 10 znaků - Celé číslo - Konstantní symbol
        'RF' => null,
        // Max. 16 znaků - Identifikátor platby pro příjemce.
        'RN' => null,
        // Max. 35 znaků - Jméno příjemce.
        'PT' => null,
        // Právě 3 znaky - Typ platby.
        'CRC32' => null,
        // Právě 8 znaků - Kontrolní součet - HEX.
        'NT' => null,
        // Právě 1 znak P|E - Identifikace kanálu pro zaslání notifikace výstavci platby.
        'NTA' => null,
        // Max. 320 znaků - Telefonní číslo v mezinárodním nebo lokálním vyjádření nebo E-mailová adresa
        'X-PER' => null,
        // Max. 2 znaky - Celé číslo - Počet dní, po které se má provádět pokus o opětovné provedení neúspěšné platby
        'X-ID' => null,
        // Max. 20 znaků. - Identifikátor platby na straně příkazce. Jedná se o interní ID,
        // jehož použití a interpretace závisí na bance příkazce.
        'X-URL' => null,
        // Max. 140 znaků. - URL, které je možno využít pro vlastní potřebu
    ];

    /**
     * Logo parameters for QR code.
     *
     * @var array{
     *   path: string,
     *   resizeToWidth: int|null,
     *   resizeToHeight: int|null,
     *   punchoutBackground: bool|null
     * }
     */
    private array $logo = [
        'path' => '',
        'resizeToWidth' => null,
        'resizeToHeight' => null,
        'punchoutBackground' => null,
    ];

    /**
     * Label parameters for QR code.
     *
     * @var array{
     *     text?: string,
     *     alignment?: LabelAlignment,
     *     margin?: array{int,int,int,int},
     *     textColor?: array{int,int,int,int}
     * }
     */
    private array $label = [];

    /**
     * New payment constructor.
     *
     * @param null $account
     * @param null $amount
     * @param null $variable
     * @param null $currency
     * @throws InvalidArgumentException
     */
    public function __construct($account = null, $amount = null, $variable = null, $currency = null)
    {
        if ($account) {
            $this->setAccount($account);
        }
        if ($amount) {
            $this->setAmount($amount);
        }
        if ($variable) {
            $this->setVariableSymbol($variable);
        }
        if ($currency) {
            $this->setCurrency($currency);
        }
    }

    /**
     * Static constructor for new payment.
     *
     * @param null $account
     * @param null $amount
     * @param null $variable
     *
     * @return QR
     * @throws InvalidArgumentException
     */
    public static function create($account = null, $amount = null, $variable = null): QR
    {
        return new self($account, $amount, $variable);
    }

    /**
     * Setting the account number in the format 12-3456789012/0100.
     *
     * @param string $account
     *
     * @return $this
     */
    public function setAccount(string $account): static
    {
        $this->spd_keys['ACC'] = self::accountToIban($account);

        return $this;
    }

    /**
     * Setting up IBAN (+SWIFT/BIC) account number
     *
     * @param string $iban
     *
     * @return $this
     */
    public function setIban(string $iban): static
    {
        $this->spd_keys['ACC'] = $iban;

        return $this;
    }

    /**
     * Setting the amount.
     *
     * @param float $amount
     *
     * @return $this
     */
    public function setAmount(float $amount): static
    {
        $this->spd_keys['AM'] = sprintf('%.2f', $amount);

        return $this;
    }

    /**
     * Variable symbol settings.
     *
     * @param int $vs
     *
     * @return $this
     */
    public function setVariableSymbol(int $vs): static
    {
        $this->spd_keys['X-VS'] = (string) $vs;

        return $this;
    }

    /**
     * Setting the constant symbol.
     *
     * @param int $ks
     *
     * @return $this
     */
    public function setConstantSymbol(int $ks): static
    {
        $this->spd_keys['X-KS'] = (string) $ks;

        return $this;
    }

    /**
     * Setting a specific symbol.
     *
     * @param int $ss
     *
     * @throws QRException
     *
     * @return $this
     */
    public function setSpecificSymbol(int $ss): static
    {
        $ssString = (string) $ss;

        if (mb_strlen($ssString) > 10) {
            throw new QRException('Specific symbol is longer than 10 characters');
        }

        $this->spd_keys['X-SS'] = $ssString;

        return $this;
    }

    /**
     * Message settings for the recipient. Diacritics will be removed from the string.
     *
     * @param string $msg
     *
     * @return $this
     */
    public function setMessage(string $msg): static
    {
        $this->spd_keys['MSG'] = mb_substr($this->stripDiacritics($msg), 0, 60);

        return $this;
    }

    /**
     * Set the payment date.
     *
     * @param \DateTime $date
     *
     * @return $this
     */
    public function setDueDate(\DateTime $date): static
    {
        $this->spd_keys['DT'] = $date->format('Ymd');

        return $this;
    }

    /**
     * Set the currency.
     *
     * @param string $cc
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setCurrency(string $cc): static
    {
        if (!in_array($cc, self::$currencies, true)) {
            throw new InvalidArgumentException(sprintf('Currency %s is not supported.', $cc));
        }

        $this->spd_keys['CC'] = $cc;

        return $this;
    }

    /**
     * Sets the label for the QR code.
     *
     * @param string $text The text of the QR code label.
     * @param array<int, int>|null $textColor An array of four integers [r, g, b, a]
     * @param array<int, int>|null $margin An array of four integers [top, right, bottom, left] for the label margins.
     * @param string|null $alignment The label alignment (default is Center).
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setLabel(
        string $text,
        ?array $textColor = null,
        ?array $margin = null,
        ?string $alignment = 'center'
    ): static {
        $textColor = $textColor ?? [0, 0, 0, 0];
        if (count($textColor) !== 4) {
            throw new InvalidArgumentException('The RGB field must have exactly 4 values.');
        }

        $margin = $margin ?? [10, 10, 20, 10];
        if (count($margin) !== 4) {
            throw new InvalidArgumentException('The margin array must have exactly 4 values.');
        }

        if (!in_array($alignment, ['center', 'left', 'right'], true)) {
            throw new InvalidArgumentException('Alignment must be "center", "left" or "right".');
        }

        $this->label = [
            'text' => $text,
            'textColor' => [
                $textColor[0],
                $textColor[1],
                $textColor[2],
                $textColor[3],
            ],
            'margin' => [
                $margin[0],
                $margin[1],
                $margin[2],
                $margin[3],
            ],
            'alignment' => match ($alignment) {
                'left' => LabelAlignment::Left,
                'right' => LabelAlignment::Right,
                default => LabelAlignment::Center,
            },
        ];

        return $this;
    }

    /**
     * Setting the logo in the center of the QR code
     *
     * @param string $path
     * @param int|null $resizeToWidth
     * @param int|null $resizeToHeight
     * @param bool $punchoutBackground
     *
     * @return static
     */
    public function setLogo(
        string $path,
        ?int $resizeToWidth = 50,
        ?int $resizeToHeight = 50,
        ?bool $punchoutBackground = true
    ): static {
        $this->logo = [
            'path' => dirname(__DIR__) . $path,
            'resizeToWidth' => $resizeToWidth,
            'resizeToHeight' => $resizeToHeight,
            'punchoutBackground' => $punchoutBackground,
        ];

        return $this;
    }

    /**
     * The method returns a QR Payment or Invoice with integrated QR Payment as a text string.
     *
     * @return string
     */
    public function __toString()
    {
        $encoded_string = '';

        // QR Payment
        $chunks = ['SPD', self::SPD_VERSION];
        foreach ($this->spd_keys as $key => $value) {
            if (null === $value) {
                continue;
            }
            $chunks[] = $key . ':' . $value;
        }
        $encoded_string .= implode('*', $chunks);

        return $encoded_string;
    }

    /**
     * The method returns the QR code as an HTML tag, or as a data-uri.
     *
     * @param bool $htmlTag
     * @param int $size
     * @param int $margin
     *
     * @return string
     */
    public function getQRCodeImage(bool $htmlTag = true, int $size = 300, int $margin = 10): string
    {
        $qrCode = $this->getQRCodeInstance($size, $margin);
        $writer = new PngWriter();

        $logo = !empty($this->logo['path']) ? new Logo(
            path: $this->logo['path'] ?? '',
            resizeToWidth: $this->logo['resizeToWidth'] ?? 50,
            resizeToHeight: $this->logo['resizeToHeight'] ?? 50,
            punchoutBackground: $this->logo['punchoutBackground'] ?? false
        ) : null;

        $baseElementSize = 250;
        $baseFontSize = 16;
        $fontSize = (int) round(($size / $baseElementSize) * $baseFontSize);

        $label = !empty($this->label) ? new Label(
            text: $this->label['text'] ?? '',
            font: new OpenSans($fontSize),
            alignment: $this->label['alignment'] ?? LabelAlignment::Center,
            margin: new Margin(...($this->label['margin'] ?? [10, 10, 20, 10])),
            textColor: new Color(...($this->label['textColor'] ?? [0, 0, 0, 0])),
        ) : null;

        $data = $writer->write($qrCode, $logo, $label)->getDataUri();

        return $htmlTag
            ? sprintf('<img src="%s" width="%2$d" height="%2$d" alt="QR Platba" />', $data, $size)
            : $data;
    }

    /**
     * Save the QR code to a file.
     *
     * @param string $filename File name of the QR Code
     * @param string|null $format Format of the file (png, jpeg, jpg, gif, wbmp)
     * @param int|null $size Size of image
     * @param int|null $margin Margin of image
     *
     * @return static
     * @throws Exception
     */
    public function saveQRCodeImage(
        string $filename,
        ?string $format = 'webp',
        ?int $size = 300,
        ?int $margin = 10
    ): static {
        $qrCode = $this->getQRCodeInstance($size, $margin);

        $writer = match ($format) {
            'webp' => new WebPWriter(),
            'png' => new PngWriter(),
            'svg' => new SvgWriter(),
            'pdf' => new PdfWriter(),
            'eps' => new EpsWriter(),
            'bin' => new BinaryWriter(),
            default => throw new QRException('Unknown file format'),
        };

        $writer->write($qrCode)->saveToFile($filename . '.' . $format);

        return $this;
    }

    /**
     * QrCode class instance
     *
     * @param int|null $size Size of image
     * @param int|null $margin Margin of image
     *
     * @return QrCodeInterface
     */
    public function getQRCodeInstance(
        ?int $size = 300,
        ?int $margin = 10
    ): QrCodeInterface {
        $size = $size ?? 300;
        $margin = $margin ?? 10;

        return new QrCode(
            data: (string) $this,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: $margin,
            roundBlockSizeMode: RoundBlockSizeMode::Enlarge,
            foregroundColor: new Color(0, 0, 0, 0),
            backgroundColor: new Color(255, 255, 255, 0),
        );
    }

    /**
     * Converting an account number to a format IBAN.
     *
     * @param string $accountNumber
     *
     * @return string
     */
    public static function accountToIban(string $accountNumber): string
    {
        $accountNumber = explode('/', $accountNumber);
        $bank = $accountNumber[1];
        $pre = 0;
        $acc = 0;
        if (false === mb_strpos($accountNumber[0], '-')) {
            $acc = $accountNumber[0];
        } else {
            list($pre, $acc) = explode('-', $accountNumber[0]);
        }

        $accountPart = sprintf('%06d%010s', $pre, $acc);
        $iban = 'CZ00' . $bank . $accountPart;

        $alfa = 'A B C D E F G H I J K L M N O P Q R S T U V W X Y Z';
        $alfa = explode(' ', $alfa);

        $alfa_replace = [];
        for ($i = 1; $i < 27; ++$i) {
            $alfa_replace[] = (string)($i + 9);
        }

        $controlegetal = str_replace(
            $alfa,
            $alfa_replace,
            mb_substr($iban, 4) . mb_substr($iban, 0, 2) . '00'
        );

        $controlegetal = 98 - (int) bcmod($controlegetal, '97');
        $iban = sprintf('CZ%02d%04d%06d%010s', $controlegetal, $bank, $pre, $acc);

        return $iban;
    }

    /**
     * Removing diacritics.
     *
     * @param string $string
     *
     * @return string
     */
    private function stripDiacritics(string $string): string
    {
        return str_replace(
            [
                'ě', 'š', 'č', 'ř', 'ž', 'ý', 'á', 'í', 'é', 'ú', 'ů',
                'ó', 'ť', 'ď', 'ľ', 'ň', 'ŕ', 'â', 'ă', 'ä', 'ĺ', 'ć',
                'ç', 'ę', 'ë', 'î', 'ń', 'ô', 'ő', 'ö', 'ů', 'ű', 'ü',
                'Ě', 'Š', 'Č', 'Ř', 'Ž', 'Ý', 'Á', 'Í', 'É', 'Ú', 'Ů',
                'Ó', 'Ť', 'Ď', 'Ľ', 'Ň', 'Ä', 'Ć', 'Ë', 'Ö', 'Ü'
            ],
            [
                'e', 's', 'c', 'r', 'z', 'y', 'a', 'i', 'e', 'u', 'u',
                'o', 't', 'd', 'l', 'n', 'a', 'a', 'a', 'a', 'a', 'a',
                'c', 'e', 'e', 'i', 'n', 'o', 'o', 'o', 'u', 'u', 'u',
                'E', 'S', 'C', 'R', 'Z', 'Y', 'A', 'I', 'E', 'U', 'U',
                'O', 'T', 'D', 'L', 'N', 'A', 'C', 'E', 'O', 'U'
            ],
            $string
        );
    }
}
