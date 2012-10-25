<?php
/*
 * (c) Netvlies Internetdiensten
 *
 * Sjoerd Peters <speters@netvlies.net>
 * 10-9-12
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Netvlies\Bundle\RouteBundle\Exception;

use Exception;

class ValidationException extends Exception
{
    /**
     * @var array $errors
     */
    private $errors;

    /**
     * @param string $message
     * @param array $errors
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($message = "", $errors = array(), $code = 0, Exception $previous = null) 
    {
        $this->errors = $errors;
        parent::__construct($message , $code, $previous);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
