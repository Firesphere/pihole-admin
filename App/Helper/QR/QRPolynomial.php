<?php

namespace App\Helper\QR;

class QRPolynomial
{
    protected $num;

    public function __construct($num, $shift = 0)
    {
        $offset = 0;

        while ($offset < count($num) && $num[$offset] === 0) {
            $offset++;
        }

        $this->num = QRMath::createNumArray(count($num) - $offset + $shift);
        for ($i = 0; $i < count($num) - $offset; $i++) {
            $this->num[$i] = $num[$i + $offset];
        }
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function toString()
    {
        $buffer = "";

        for ($i = 0; $i < $this->getLength(); $i++) {
            if ($i > 0) {
                $buffer .= ",";
            }
            $buffer .= $this->get($i);
        }

        return $buffer;
    }

    // PHP5

    public function getLength()
    {
        return count($this->num);
    }

    public function get($index)
    {
        return $this->num[$index];
    }

    public function toLogString()
    {
        $buffer = "";

        for ($i = 0; $i < $this->getLength(); $i++) {
            if ($i > 0) {
                $buffer .= ",";
            }
            $buffer .= QRMath::glog($this->get($i));
        }

        return $buffer;
    }

    /**
     * @param QRPolynomial $e
     *
     * @return QRPolynomial
     */
    public function multiply($e)
    {
        $num = QRMath::createNumArray($this->getLength() + $e->getLength() - 1);

        for ($i = 0; $i < $this->getLength(); $i++) {
            $vi = QRMath::glog($this->get($i));

            for ($j = 0; $j < $e->getLength(); $j++) {
                $num[$i + $j] ^= QRMath::gexp($vi + QRMath::glog($e->get($j)));
            }
        }

        return new QRPolynomial($num);
    }

    /**
     * @param QRPolynomial $e
     *
     * @return $this|QRPolynomial
     */
    public function mod($e)
    {
        if ($this->getLength() - $e->getLength() < 0) {
            return $this;
        }

        $ratio = QRMath::glog($this->get(0)) - QRMath::glog($e->get(0));

        $num = QRMath::createNumArray($this->getLength());
        for ($i = 0; $i < $this->getLength(); $i++) {
            $num[$i] = $this->get($i);
        }

        for ($i = 0; $i < $e->getLength(); $i++) {
            $num[$i] ^= QRMath::gexp(QRMath::glog($e->get($i)) + $ratio);
        }

        return (new self($num))->mod($e);
    }
}
