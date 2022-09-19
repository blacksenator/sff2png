<?PHP

namespace blacksenator\sff;

require_once 'mhDecoder.php';

use blacksenator\sff\mhDecoder;

/**
 * This class provides functions to read page data (fax image mh coded)
 *
 * @see https://ftp.avm.de/archive/develper/capispec/capi20/capi20-1.pdf -> ANNEX B (NORMATIVE): SFF FORMAT
 * @see http://delphi.pjh2.de/articles/graphic/sff_format.php
 * @author Volker Püschel <knuffy@anasco.de>
 * @copyright Volker Püschel 2022
 * @license MIT
 */

class sffReader
{
    const DATA_LENGTH = [
        'V' => 4,
        'v' => 2,
        'C' => 1
    ];
    /**
     * Header defintions
     * values according to https://www.php.net/manual/de/function.pack.php -> format
     */
    const SFF_HEADER_DEF = [                // 20 Bytes
        'SFF_Id'                => 'V',     // 4; unsigned long (always 32 bit, little endian byte order)
        'Version'               => 'C',     // 1; unsigned char
        'reserved'              => 'C',     // 1
        'UserInformation'       => 'v',     // 2; unsigned short (always 16 bit, little endian byte order)
        'PageCount'             => 'v',     // 2
        'OffsetFirstPageHeader' => 'v',     // 2
        'OffsetLastPageHeader'  => 'V',     // 4
        'OffsetDocumentEnd'     => 'V',     // 4
    ];
    const PAGE_HEADER_DEF = [               // 18 Bytes
        'PageHeaderID'          => 'C',     // 1
        'PageHeaderLen'         => 'C',     // 1; value count up from "ResolutionVertical"
        'ResolutionVertical'    => 'C',     // 1
        'ResolutionHorizontal'  => 'C',     // 1
        'Coding'                => 'C',     // 1
        'reserved'              => 'C',     // 1
        'LineLength'            => 'v',     // 2
        'PageLength'            => 'v',     // 2
        'OffsetPreviousPage'    => 'V',     // 4
        'OffsetNextPage'        => 'V',     // 4
    ];
    const PAGE_HEAD_OFFSET = 2;                         // see getPageHeader()
    const WHITE_1728 = [1 => 178, 2 => 89, 3 => 1];     // byte sequence for white (empty) line
    const WHITE_2048 = [1 => 128, 2 => 204, 3 => 10];   // see unpack for indexing!
    const WHITE_2432 = [1 => 128, 2 => 203, 3 => 10];

    private $pointer = 0;
	private $fileData;
	private $fileSize;
    private $docHeader = [];
    private $pageHeaderDef = '';
    private $pageHeaderLen = 0;
    private $endOfSFF = false;
    private $decoder = null;

    public function __construct(string $fileData)
    {
        $this->fileData = $fileData;
		$this->fileSize = strlen($fileData);
        $docHeaderDef = $this->getDefString(self::SFF_HEADER_DEF);
        $docHeaderLen = $this->getHeaderLength(self::SFF_HEADER_DEF);
        $this->docHeader = $this->getDocHeader($docHeaderDef, $docHeaderLen);
        $this->pageHeaderDef = $this->getDefString(self::PAGE_HEADER_DEF);
        $this->pageHeaderLen = $this->getHeaderLength(self::PAGE_HEADER_DEF);
        $this->decoder = new mhDecoder();
    }

    /**
     * returns a string which defines the fields and data types
     *
     * @see https://www.php.net/manual/de/function.unpack.php -> example #1
     *
     * @param array $definition
     * @return string $formatStr format string
     */
    private function getDefString(array $definition): string
    {
        $formatStr = '';
        foreach ($definition as $key => $value) {
            $formatStr .= $value . $key . '/';  // assemble string from key and value (datatype)
        }

        return substr($formatStr, 0, -1);               // delete the last '/'
    }

    /**
     * return the byte length of dedicated data types
     *
     * @param string $dataType
     * @return int $length
     */
    private function getTypeLength(string $dataType): int
    {
        return self::DATA_LENGTH[$dataType];
    }

    /**
     * return the byte length of headers
     *
     * @param array $headerDef
     * @return int $length
     */
    private function getHeaderLength(array $headerDef): int
    {
        $length = 0;
        foreach ($headerDef as $dataType) {
            $length += $this->getTypeLength($dataType);
        }

        return $length;
    }

    /**
     * set pointer to new adress
     *
     * @param int $adress
     * @return void
     */
    private function setPointer(int $adress)
    {
        if ($adress < $this->fileSize - 1) {
            $this->pointer = $adress;
        } elseif (($adress = $this->fileSize - 1)) {
            $this->endOfSFF = true;
        } else {
			throw new \Exception('Pointer adress is beyond file size!');
		}
    }

    /**
     * return the additional header data
     *
     * @param int $start
     * @param int $length
     * @return array
     */
    private function getAdditional(int $start, int $length): array
    {
        return ['additional' => substr($this->fileData, $start, $length)];
    }

    /**
     * returns the document header
     *
     * $additional: "...there could be additional userspecific data between the
     * document header and the first page..."
     *
     * @param string $docHeaderDef
     * @param int $docHeaderLen
     * @return array $header document header
     */
    private function getDocHeader(string $docHeaderDef, int $docHeaderLen): array
    {
        $header = unpack($docHeaderDef, $this->fileData, 0);
        $additional = $header['OffsetFirstPageHeader'] - $docHeaderLen;
        if ($additional) {
            $header = array_merge($header, $this->getAdditional($docHeaderLen, $additional));
        }
        $this->setPointer($header['OffsetFirstPageHeader']);

        return $header;
    }

    /**
     * returns the SFF document header
     *
     * @return array $docHeader
     */
    public function getSFFHeader()
    {
        return $this->docHeader;
    }

    /**
     * get the page header
     *
     * $additional: "...there may be additional user-specific data between page
     * header and page data..."
     * Surprisingly, the data definition is 18 bytes long, but the header length
     * in the header itself is only specified as 16 bytes long. Hence the
     * addition of +2 (PAGE_HEAD_OFFSET), since otherwise the determination of
     * any optional additional data cannot be determined.
     *
     * @param string $pageHeaderDef
     * @param int $pageHeaderLen
     * @return array $header page header
     */
    private function getPageHeader(string $pageHeaderDef, int $pageHeaderLen)
    {
        $header = unpack($pageHeaderDef, $this->fileData, $this->pointer);
        $additional = $header['PageHeaderLen'] + self::PAGE_HEAD_OFFSET - $pageHeaderLen;    // see above
        if ($additional) {
            $start = $this->pointer + $pageHeaderLen;
            $header = array_merge($header, $this->getAdditional($start, $additional));
        }
        $this->setPointer($this->pointer + $header['PageHeaderLen'] + self::PAGE_HEAD_OFFSET);

        return $header;
    }

    /**
     * return the byte(s) value from current pointer position
     *
     * @param string $formatStr
     * @return int $values
     */
    private function getValue(string $formatStr): int
    {
        $typeLength = $this->getTypeLength($formatStr);
		$byteSequenze = substr($this->fileData, $this->pointer, $typeLength);
        $this->setPointer($this->pointer + $typeLength);

        return unpack($formatStr, $byteSequenze)[1];
    }

    /**
     * get the length (number of bytes) the record consists of
     *
     * Possible values determing the function process:
     * 1..216:   a pixel row with 1..216 MH-coded bytes follows immediately
     * 0:        escape code for a pixel row with more than 216 MH-coded bytes
     *           In this case, the following word in the range 217..32767
     *           defines the number of MH-coded bytes which follow.
     * 217..253: white space, skip 1..37 empty lines (will output: -1..-37)
     * 255:      if followed by a byte with value 0, illegal line coding.
     *           Applications may choose whether to interpret this line as
     *           empty or as a copy of the previous line.
     *           If this byte is followed by a byte with a value 1...255,
     *           then 1...255 bytes of additional user information follow
     *           (reserved for future extensions)
     *
     * @return int
     */
    private function getRecordLength()
    {
        $numberOfBytes = false;
        if (!$this->endOfSFF) {
            $numberOfBytes = $this->getValue('C');      // default length value
            if ($numberOfBytes > 216 && $numberOfBytes < 254) {
                $numberOfBytes = ($numberOfBytes - 216) * -1;
            } elseif ($numberOfBytes === 0) {               // a word following
                $numberOfBytes = $this->getValue('v');
            } elseif ($numberOfBytes === 255) {         // usually wrong, but...
                if ($this->getValue('C') === 0) {
                    $numberOfBytes = -1;                    // one  blank line
                } else {
                    throw new \Exception(\sprintf('Unsupportet page record length %s!', $numberOfBytes));
                };
            }
        }

        return $numberOfBytes;
    }

    /**
     * return the byte sequence of a record from current pointer position
     *
     * @param int $numberOfBytes
     * @return array of int
     */
    private function getRecord(int $numberOfBytes)
    {
        $binaryData = substr($this->fileData, $this->pointer, $numberOfBytes);
        $this->setPointer($this->pointer + $numberOfBytes);
        $record = unpack(sprintf('C%s', $numberOfBytes), $binaryData);
        if ($record == false) {
            throw new \Exception('Could not unpack record!');
        }

        return $record;
    }

    /**
     * read lines of page data and decode them into an array:
     * - key = line vertical
     * - value(s) = [postion of black pixel => length]
     *
     * @param array $whiteLine
     * @return array $lines
     */
    private function getLinesOfPage(array $whiteLine)
    {
        $yPos = 0;
        $lines = [];
        $numberOfBytes = $this->getRecordLength();
        while (!$this->endOfSFF && $numberOfBytes !== 254) {
            if ($numberOfBytes > 0) {
                $record = $this->getRecord($numberOfBytes);
                $record == $whiteLine ?: $lines[$yPos] = $this->decoder->decodeLine($record);
                $yPos++;
            } else {                    // negativ values indicate empty line(s)
                $yPos += ($numberOfBytes * -1);
            }
            $numberOfBytes = $this->getRecordLength();
        }
        if ($numberOfBytes == 254) {    // skip back to startpoint of page header
            $this->setPointer($this->pointer - 1);
        }
        $lines[$yPos] = $lines[$yPos] ?? [0 => 0];

        return $lines;
    }

    /**
     * returns an array of bytes indicating a white line according to the line
     * length
     *
     * @param int $lineLength
     * @return array $whiteline
     */
    private function getWhiteLine(int $lineLength)
    {
        if ($lineLength === 1728) {
            $whiteLine = self::WHITE_1728;
        } elseif ($lineLength === 2048) {
            $whiteLine = self::WHITE_2048;
        } elseif ($lineLength === 2432) {
            $whiteLine = self::WHITE_2432;
        } else {
            throw new \Exception(sprintf('Unsupportet line length %s!', $lineLength));
        }

        return $whiteLine;
    }

    /**
     * return the page data as an array with header and lines of bytes
     *
     * @return array
     */
    private function getPage()
    {
        $pageHeader = $this->getPageHeader($this->pageHeaderDef, $this->pageHeaderLen);
        $lineLength = $pageHeader['LineLength'];
        $whiteLine = $this->getWhiteLine($lineLength);
        !($lineLength > 1728) ?: $this->decoder->setBinaryArraysExtended();
        $headerPageLen = $pageHeader['PageLength'];
        $pageLines = $this->getLinesOfPage($whiteLine);
        $lastLine = end($pageLines);
        $pageLength = key($pageLines);
        !($lastLine == [0 => 0]) ?: array_pop($pageLines);
        $pageHeader['PageLength'] = $pageLength;
        if ($headerPageLen > 0 && $headerPageLen <> $pageLength) {
            echo sprintf('Header page length: %s, determined page length: %s!', $headerPageLen, $pageLength);
        }

        return [
            'PageHeader' => $pageHeader,
            'PageLines'  => $pageLines,
        ];
    }

    /**
     * return array of pages
     * each page consists of the arrays 'PageHeader' and 'PageLines'. PageLines
     * contains the line (y position) as a key and an array with the position
     * x and the running length of the black pixels as values.
     *
     * @return array $pages
     */
    public function getPages()
    {
        while (!$this->endOfSFF) {
            $page = $this->getPage();
            $pages[] = $page;
        }

        return $pages;
    }
}
