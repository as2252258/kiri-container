<?php
declare(strict_types=1);

namespace Kiri\Di\Interface;

use Psr\Http\Message\ResponseInterface;

interface ResponseEmitter
{


	/**
	 * @param ResponseInterface $proxy
	 * @param object $response
	 * @return void
	 */
	public function sender(ResponseInterface $proxy, object $response): void;


}
