<?php
namespace Spaceboy\ImageText;

class ImageText
{
    const ALIGN_LEFT = 'left';
    const ALIGN_RIGHT = 'right';
    const ALIGN_CENTER = 'center';
    const ALIGN_JUSTIFY = 'justify';

    const MSG_COLOR_ERR = 'Wrong color format; color can be defined as "#RGB", "#RRGGBB", [R, G, B] or [R, G, B, A].';

    /** @var string $text Text to write */
    private ?string $text = null;

    /** @var image resource $image */
    private $image;

    /** @var int $width Output image width */
    private ?int $width = null;

    /** @var int $innerWidth Text zone width ($width - $paddingLeft - $paddingRight) */
    private int $innerWidth;

    /** @var string $fontPath Path to font file */
    private string $fontPath;

    /** @var float $fontSize Font size (automatic when 0) */
    private float $fontSize = 0;

    /** @var int $paddingTop */
    private int $paddingTop = 0;

    /** @var int $paddingRight */
    private int $paddingRight = 0;

    /** @var int $paddingBottom */
    private int $paddingBottom = 0;

    /** @var int $paddingLeft */
    private int $paddingLeft = 0;

    /** @var $bgColor Background color */
    private $bgColor = [0, 0, 0, 127];

    /** @var $bgColor Text color */
    private $textColor = [255, 255, 255];

    /** @var int $lineHeight Line height in pixels (set automatically when 0) */
    private int $lineHeight = 0;

    /** @var string $alignation Text alignation */
    private string $alignation = self::ALIGN_LEFT;

    /** @var string[] $words Parsed words */
    private array $words;

    /** @var array[] $lines Parsed lines */
    private array $lines;

    /** @var int $lineOffset Vertical line offset */
    private int $lineOffset = 0;

    /** @var int $initialHeadlineScale Scale for computing automatic font size (higher is better but takes higher resources) */
    private int $initialHeadlineScale = 1000;

    /**
     * Sets text alignment.
     * @param string $alignation
     * @return ImageText
     * @throws \Exception
     */
    public function setAlign(string $alignation): self
    {
        if (
            !in_array(
                $alignation,
                [
                    static::ALIGN_LEFT,
                    static::ALIGN_RIGHT,
                    static::ALIGN_CENTER,
                ]
            )
        ) {
            throw new \Exception('Wrong alignation.');
        }
        $this->alignation = $alignation;
        return $this;
    }

    /**
     * Sets background color.
     * @param mixed $color ('#RGB', '#RRGGBB', [R, G, B], [R, G, B, A])
     * @return ImageText
     * @throws \Exception
     */
    public function setBackgroundColor($color): self
    {
        $this->bgColor = $this->convertColor($color);
        return $this;
    }

    /**
     * Sets text color.
     * @param mixed $color ('#RGB', '#RRGGBB', [R, G, B], [R, G, B, A])
     * @return ImageText
     * @throws \Exception
     */
    public function setColor($color): self
    {
        $this->textColor = $this->convertColor($color);
        return $this;
    }

    /**
     * Sets paragraph font.
     * @param string $fontPath (path to font file)
     * @param float $fontSize (font size)
     * @return ImageText
     * @throws \Exception
     */
    public function setFont(string $fontPath, float $fontSize = null): self
    {
        if (!is_file($fontPath) || !is_readable($fontPath)) {
            throw new \Exception("Font not found or is not readable ({$fontPath}).");
        }
        $this->fontPath = $fontPath;
        return (
            $fontSize === null
            ? $this
            : $this->setFontSize($fontSize)
        );
    }

    /**
     * Sets font size.
     * @param float $fontSize
     * @return ImageText
     */
    public function setFontSize(float $fontSize): self
    {
        $this->fontSize = $fontSize;
        return $this;
    }

    /**
     * Sets initial headline scale.
     * @param int $initialHeadlineScale
     * @return ImageText
     */
    public function setInitialHeadlineScale(int $initialHeadlineScale): self
    {
        $this->initialHeadlineScale = $initialHeadlineScale;
        return $this;
    }

    /**
     * Sets line height (when unset, line height is set automatically).
     * @param int $lineHeight
     * @return ImageText
     */
    public function setLineHeight(int $lineHeight): self
    {
        $this->lineHeight = $lineHeight;
        return $this;
    }

    /**
     * Sets vertical line offset (when unset, offset is set automatically).
     * @param int $lineOffset
     * @return ImageText
     */
    public function setLineOffset(int $lineOffset): self
    {
        $this->lineOffset = $lineOffset;
        return $this;
    }

    /**
     * Sets text padding (in CSS format).
     * @param int $top
     * @param int $right
     * @param int $bottom
     * @param int $left
     * @return ImageText
     */
    public function setPadding(int $top, int $right = null, int $bottom = null, int $left = null): self
    {
        if ($right === null) {
            $this->paddingTop = $top;
            $this->paddingRight = $top;
            $this->paddingBottom = $top;
            $this->paddingLeft = $top;
            return $this;
        }
        if ($bottom === null) {
            $this->paddingTop = $top;
            $this->paddingRight = $right;
            $this->paddingBottom = $top;
            $this->paddingLeft = $right;
            return $this;
        }
        if ($left === null) {
            $this->paddingTop = $top;
            $this->paddingRight = $right;
            $this->paddingBottom = $bottom;
            $this->paddingLeft = $right;
            return $this;
        }
        $this->paddingTop = $top;
        $this->paddingRight = $right;
        $this->paddingBottom = $bottom;
        $this->paddingLeft = $left;
        return $this;
    }

    /**
     * Sets text to writo into image.
     * @param string $text
     * @return ImageText
     */
    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    /**
     * Sets image width.
     * @param int $width
     * @return ImageText
     */
    public function setWidth(int $width): self
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Returns line offset.
     * @return int
     */
    public function getLineOffset(): int
    {
        return $this->lineOffset;
    }

    /**
     * Returns bottom padding.
     * @return int
     */
    public function getPaddingBottom(): int
    {
        return $this->paddingBottom;
    }

    /**
     * Returns left padding.
     * @return int
     */
    public function getPaddingLeft(): int
    {
        return $this->paddingLeft;
    }

    /**
     * Returns right padding.
     * @return int
     */
    public function getPaddingRight(): int
    {
        return $this->paddingRight;
    }

    /**
     * Returns top padding.
     * @return int
     */
    public function getPaddingTop(): int
    {
        return $this->paddingTop;
    }

    /**
     * Converts colors from different formats to array.
     * @param string|array $color
     * @return array
     * @throws \Exception
     */
    private function convertColor($color): array
    {
        if (is_array($color)) {
            $color = array_filter(
                $color,
                function ($item) {
                    if (!is_int($item)) {
                        return false;
                    }
                    if ($item < 0 || $item > 255) {
                        return false;
                    }
                    return true;
                }
            );
            $count = count($color);
            if (!in_array($count, [3, 4])) {
                throw new \Exception(static::MSG_COLOR_ERR);
            }
            return $color;
        }
        if (!is_string($color)) {
            throw new \Exception(static::MSG_COLOR_ERR);
        }
        if (
            !preg_match('/^#[0-9A-F]{3}$/i', $color)
            && !preg_match('/^#[0-9A-F]{6}$/i', $color)
        ) {
            throw new \Exception(static::MSG_COLOR_ERR);
        }
        $color = substr($color, 1);
        return array_map(
            function ($item) {
                return hexdec(
                    strlen($item) === 1
                    ? $item . $item
                    : $item
                );
            },
            str_split(
                $color,
                3 === strlen($color)
                ? 1
                : 2
            )
        );
    }

    /**
     * Allocates color to $this->image.
     * @param array $param Color (as array [R, G, B] or [R, G, B, A])
     * @return int|bool A color identifier or false if the allocation failed.
     */
    private function getColor(array $params)
    {
        array_unshift($params, $this->image);
        return call_user_func_array(
            (
                count($params) === 4
                ? '\imagecolorallocate'
                : '\imagecolorallocatealpha'
            ),
            $params
        );
    }

    /**
     * Sets image background.
     * @return void
     */
    private function setImageBackground(): void
    {
        imagefill($this->image, 0, 0, $this->getColor($this->bgColor));
    }

    /**
     * Parses text to word.
     * @return void
     */
    private function parseWords(): void
    {
        $this->words = \array_map(
            function ($item) {
                return \str_replace('&nbsp;', ' ', $item);
            },
            array_filter(
                \explode(' ', trim($this->text))
            )
        );
    }

    /**
     * Joins words to lines by its length.
     * @return int Max line height
     */
    private function joinLines(): int
    {
        $image = \imagecreatetruecolor(1, 1);
        $maxLineHeight = 0;
        $line = '';
        $lines = [];

        $word = \array_shift($this->words);

        while (true) {
            $newLine = (
                $line === ''
                ? $word
                : $line . ' ' . $word
            );
            $size = \imagettftext($image, $this->fontSize, 0, 0, 0, 0, $this->fontPath, $newLine);
            $width = $size[2] - $size[0]; // Lower-right X [2] - Lower-left X [0]
            $height = $size[1] - $size[7]; // Lower-left Y [1] - Upper left Y [7]

            if ($size[1] > $this->lineOffset) {
                $this->lineOffset = $size[1];
            }
            if ($height > $maxLineHeight) {
                $maxLineHeight = $height;
            }

            if ($width >= $this->innerWidth) {
                // Line is too wide:
                $size = \imagettftext($image, $this->fontSize, 0, 0, 0, 0, $this->fontPath, $line);
                $lines[] = [
                    'text' => $line,
                    'width' => $size[2] - $size[0],
                    'offset' => 0, //$size[0],
                ];
                $line = '';
                if ($newLine === $word) {
                    if (count($this->words) === 0) {
                        break;
                    }
                    $word = \array_shift($this->words);
                }
                continue;
            }
            // Line width is OK:
            $line = $newLine;
            if (count($this->words) === 0) {
                $lines[] = [
                    'text' => $line,
                    'width' => $width,
                    'offset' => 0, //$size[0],
                ];
                break;
            }
            $word = \array_shift($this->words);

        }

        \imagedestroy($image);
        $this->lines = $lines;

        return $maxLineHeight;
    }

    /**
     * Computes X position for line (depends on alignation, left padding, individual offset).
     * @param array $line
     * @return int
     */
    private function getPaddingLine(array $line): int
    {
        switch ($this->alignation) {
            case self::ALIGN_LEFT:
                return $this->paddingLeft - $line['offset'];
                break;
            case self::ALIGN_RIGHT:
                return $this->paddingLeft + $this->innerWidth - $line['width'];
                break;
            case self::ALIGN_CENTER:
                return round($this->paddingLeft + ($this->innerWidth - $line['width']) / 2);
                break;
        }
    }

    /**
     * Prepares settings for rendering paragraph (w/ set font size).
     * @return void
     */
    private function prepareParagraph(): void
    {
        $this->parseWords();
        $autoLineHeight = $this->joinLines();
        if (!$this->lineHeight) {
            $this->lineHeight = $autoLineHeight;
        }
    }

    /**
     * Prepares settings for rendering headline (written to full width; w/ font size = 0).
     * @return void
     */
    private function prepareHeadline(): void
    {
        $fontSize = $this->initialHeadlineScale;
        $image = \imagecreatetruecolor(1, 1);

        // First iteration:
        $size = \imagettftext($image, $fontSize, 0, 0, 0, 0, $this->fontPath, $this->text);
        $this->fontSize = $fontSize / (($size[2] - $size[0]) / $this->innerWidth);

        // Fine tuning:
        $size = \imagettftext($image, $this->fontSize, 0, 0, 0, 0, $this->fontPath, $this->text);
        $this->lineOffset = $size[1];
        $this->lineHeight = $size[1] - $size[7];

        $this->lines = [
            [
                'width' => $size[2] - $size[0],
                'text' => $this->text,
                'offset' => $size[0],
            ]
        ];
        \imagedestroy($image);
    }

    /**
     * Returns created image.
     * @return image resource (gd)
     * @throws \Exception
     */
    public function getImage()
    {
        if ($this->width === null) {
            throw new \Exception('Undefined image width; use setWidth method.');
        }
        if ($this->text === null) {
            throw new \Exception('Undefined text; use setText method.');
        }
        if ($this->fontPath === null) {
            throw new \Exception('Undefined font; use setFont method.');
        }
        $this->innerWidth = $this->width - $this->paddingLeft - $this->paddingRight;

        if ($this->fontSize) {
            $this->prepareParagraph();
        } else {
            $this->prepareHeadline();
        }

        $this->image = imagecreatetruecolor(
            $this->width,
            $this->paddingTop + $this->paddingBottom + (count($this->lines) * $this->lineHeight)
        );

        $this->setImageBackground();

        $color = $this->getColor($this->textColor);
        $posY = $this->paddingTop + $this->lineHeight - $this->lineOffset;

        foreach ($this->lines as $line) {
            imagettftext(
                $this->image,                   // image
                $this->fontSize,                // font size
                0,                              // angle
                $this->getPaddingLine($line),   // x position
                $posY,                          // y position
                $color,                         // text color
                $this->fontPath,                // font file
                $line['text']                   // text
            );
            $posY += $this->lineHeight;
        }

        return $this->image;
    }
}
