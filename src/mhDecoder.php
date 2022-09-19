<?PHP

namespace blacksenator\sff;

require_once 'mhCodes.php';

/**
 * This class provides functions to decode modified huffman
 *
 * @see https://ftp.avm.de/archive/develper/capispec/capi20/capi20-1.pdf -> ANNEX B (NORMATIVE): SFF FORMAT
 * @see http://delphi.pjh2.de/articles/graphic/sff_format.php
 * @author Volker Püschel <knuffy@anasco.de>
 * @copyright Volker Püschel 2022
 * @license MIT
 */

class mhDecoder
{
    const LSBF = [
        0 => 0,
        1 => 128,
        2 => 64,
        3 => 192,
        4 => 32,
        5 => 160,
        6 => 96,
        7 => 224,
        8 => 16,
        9 => 144,
        10 => 80,
        11 => 208,
        12 => 48,
        13 => 176,
        14 => 112,
        15 => 240,
        16 => 8,
        17 => 136,
        18 => 72,
        19 => 200,
        20 => 40,
        21 => 168,
        22 => 104,
        23 => 232,
        24 => 24,
        25 => 152,
        26 => 88,
        27 => 216,
        28 => 56,
        29 => 184,
        30 => 120,
        31 => 248,
        32 => 4,
        33 => 132,
        34 => 68,
        35 => 196,
        36 => 36,
        37 => 164,
        38 => 100,
        39 => 228,
        40 => 20,
        41 => 148,
        42 => 84,
        43 => 212,
        44 => 52,
        45 => 180,
        46 => 116,
        47 => 244,
        48 => 12,
        49 => 140,
        50 => 76,
        51 => 204,
        52 => 44,
        53 => 172,
        54 => 108,
        55 => 236,
        56 => 28,
        57 => 156,
        58 => 92,
        59 => 220,
        60 => 60,
        61 => 188,
        62 => 124,
        63 => 252,
        64 => 2,
        65 => 130,
        66 => 66,
        67 => 194,
        68 => 34,
        69 => 162,
        70 => 98,
        71 => 226,
        72 => 18,
        73 => 146,
        74 => 82,
        75 => 210,
        76 => 50,
        77 => 178,
        78 => 114,
        79 => 242,
        80 => 10,
        81 => 138,
        82 => 74,
        83 => 202,
        84 => 42,
        85 => 170,
        86 => 106,
        87 => 234,
        88 => 26,
        89 => 154,
        90 => 90,
        91 => 218,
        92 => 58,
        93 => 186,
        94 => 122,
        95 => 250,
        96 => 6,
        97 => 134,
        98 => 70,
        99 => 198,
        100 => 38,
        101 => 166,
        102 => 102,
        103 => 230,
        104 => 22,
        105 => 150,
        106 => 86,
        107 => 214,
        108 => 54,
        109 => 182,
        110 => 118,
        111 => 246,
        112 => 14,
        113 => 142,
        114 => 78,
        115 => 206,
        116 => 46,
        117 => 174,
        118 => 110,
        119 => 238,
        120 => 30,
        121 => 158,
        122 => 94,
        123 => 222,
        124 => 62,
        125 => 190,
        126 => 126,
        127 => 254,
        128 => 1,
        129 => 129,
        130 => 65,
        131 => 193,
        132 => 33,
        133 => 161,
        134 => 97,
        135 => 225,
        136 => 17,
        137 => 145,
        138 => 81,
        139 => 209,
        140 => 49,
        141 => 177,
        142 => 113,
        143 => 241,
        144 => 9,
        145 => 137,
        146 => 73,
        147 => 201,
        148 => 41,
        149 => 169,
        150 => 105,
        151 => 233,
        152 => 25,
        153 => 153,
        154 => 89,
        155 => 217,
        156 => 57,
        157 => 185,
        158 => 121,
        159 => 249,
        160 => 5,
        161 => 133,
        162 => 69,
        163 => 197,
        164 => 37,
        165 => 165,
        166 => 101,
        167 => 229,
        168 => 21,
        169 => 149,
        170 => 85,
        171 => 213,
        172 => 53,
        173 => 181,
        174 => 117,
        175 => 245,
        176 => 13,
        177 => 141,
        178 => 77,
        179 => 205,
        180 => 45,
        181 => 173,
        182 => 109,
        183 => 237,
        184 => 29,
        185 => 157,
        186 => 93,
        187 => 221,
        188 => 61,
        189 => 189,
        190 => 125,
        191 => 253,
        192 => 3,
        193 => 131,
        194 => 67,
        195 => 195,
        196 => 35,
        197 => 163,
        198 => 99,
        199 => 227,
        200 => 19,
        201 => 147,
        202 => 83,
        203 => 211,
        204 => 51,
        205 => 179,
        206 => 115,
        207 => 243,
        208 => 11,
        209 => 139,
        210 => 75,
        211 => 203,
        212 => 43,
        213 => 171,
        214 => 107,
        215 => 235,
        216 => 27,
        217 => 155,
        218 => 91,
        219 => 219,
        220 => 59,
        221 => 187,
        222 => 123,
        223 => 251,
        224 => 7,
        225 => 135,
        226 => 71,
        227 => 199,
        228 => 39,
        229 => 167,
        230 => 103,
        231 => 231,
        232 => 23,
        233 => 151,
        234 => 87,
        235 => 215,
        236 => 55,
        237 => 183,
        238 => 119,
        239 => 247,
        240 => 15,
        241 => 143,
        242 => 79,
        243 => 207,
        244 => 47,
        245 => 175,
        246 => 111,
        247 => 239,
        248 => 31,
        249 => 159,
        250 => 95,
        251 => 223,
        252 => 63,
        253 => 191,
        254 => 127,
        255 => 255,
    ];

    private $binaryArrayWhite;
    private $binaryArrayBlack;
    private $binaryArrayExtended;
    private $bitMasks;
    private $colorBit;
    private $mhCodes = null;

    public function __construct()
    {
        $this->mhCodes = new mhCodes;
        $this->binaryArrayWhite = $this->mhCodes->getBinaryArrayWhite();
        $this->binaryArrayBlack = $this->mhCodes->getBinaryArrayBlack();
        $this->binaryArrayExtended = $this->mhCodes->getBinaryArrayExtended();
        $this->bitMasks = $this->mhCodes->getBitMasks();
        $this->colorBit = 0;              // set on "white" as first color
    }

    /**
     * returns the value of an byte (8-bit) least significant bit first.
     * Using an array with precomputed values is twice as fast as a
     * transformation using eight-digit string operations, which is about a
     * third faster than a bit-oriented operation.
     *
     * @param int $byte
     * @return int
     */
    public function getByteLSBF(int $byte)
    {
        return self::LSBF[$byte];
    }

    /**
     * returns the run length and the length of the codeword (bitshift) found,
     * or false if no code mapping could found.
     * The use of references here instead of an associative array to return the
     * results is based on a recommendation in the php.de forum and has actually
     * reduced the total runtime by about 20%
     *
     * @see https://www.php.de/forum/webentwicklung/php-einsteiger/1603990-schl%C3%BCssel-f%C3%BCr-bin%C3%A4res-mehrdimensionales-array-direkt-%C3%BCbergeben?p=1604462#post1604462
     *
     * @param int $colorBit
     * @param int $sequencePart
     * @param int $shift (by reference)
     * @param int $length (by reference)
     * @return bool
     */
    private function getCodeMaping(int $colorBit, int $sequencePart, &$shift, &$length)
    {
        $map = ($colorBit == 0) ? $this->binaryArrayWhite : $this->binaryArrayBlack;
        for ($i = 2; $i < 14; $i++) {
            $searchKey = ($sequencePart & $this->bitMasks[$i]);
            if (isset($map[$searchKey][$i])) {
                $shift  = $i;
                $length = $map[$searchKey][$i];
                return true;
            }
        }
        return false;
    }

    /**
     * returns an array with positions and length of black pixel as one line. If
     * decoding fails, an empty array is returned as an empty row
     *
     * @param array $record
     * @return array $row
     */
    public function decodeLine(array $record)
    {
        $row = [];
        $xPos = 0;                              // current x position in the row
        $accumulator = 0;
        $buffer = 0;
        //initial stock up
        $accumulator = ($accumulator << 8) | $this->getByteLSBF(current($record));
        $accumulator = ($accumulator << 8) | $this->getByteLSBF(next($record));
        $accuLength = 16;
        // repetitive
        while ($accumulator > 0 && key($record) != null) {
            // continuous refill
            while ($accuLength < 16 && key($record) != null) {
                $accumulator = ($accumulator << 8) | $this->getByteLSBF(next($record));
                $accuLength += 8;
            }
            // hand over 16 bit or less into buffer
            $buffer = ($accuLength > 16) ? $accumulator >> ($accuLength - 16) : $accumulator;
            if ($this->getCodeMaping($this->colorBit, $buffer, $shift, $length)) {
                // trim accumulator left by the length of the code word found
                $accuLength = $accuLength - $shift;
                $accumulator = ($accumulator & (pow(2, $accuLength) - 1));
                // the position and number of black bits are saved
                !$this->colorBit ?: $row[$xPos] = $length;
                $xPos += $length;
                !($length < 64) ?: $this->colorBit = 1 - $this->colorBit;
            } else {
                $this->colorBit = 0;
                return [];
            }
        }
        $this->colorBit = 0;            // reset on white for next record (row)

        return $row;
    }

    /**
     * extends the codeword array with extensions if pageline is greater than
     * 1728 - which is probably not the
     *
     * @return void
     */
    public function setBinaryArraysExtended()
    {
        $this->binaryArrayWhite += $this->binaryArrayExtended;
        $this->binaryArrayBlack += $this->binaryArrayExtended;
    }
}
