<?php
declare(strict_types=1);

namespace Kiri\Di\Interface;

use Psr\Http\Message\ResponseInterface;


/**
 * Response Emitter Interface
 */
interface ResponseEmitterInterface
{


    /**
     * @param ResponseInterface $proxy
     * @param object $response
     * @param object $request
     * @return void
     */
	public function response(ResponseInterface $proxy, object $response, object $request): void;


}
