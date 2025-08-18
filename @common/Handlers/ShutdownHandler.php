<?php

declare(strict_types=1);

namespace Common\Handlers;

use Common\ResponseEmitter\ResponseEmitter;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpInternalServerErrorException;

class ShutdownHandler
{
    private Request $request;

    private HttpErrorHandler $errorHandler;

    private bool $displayErrorDetails;

    public function __construct(
        Request $request,
        HttpErrorHandler $errorHandler,
        bool $displayErrorDetails
    ) {
        $this->request = $request;
        $this->errorHandler = $errorHandler;
        $this->displayErrorDetails = $displayErrorDetails;
    }

    public function __invoke()
    {
        $error = error_get_last();
        if ($error) {
            $errorFile = $error['file'];
            $errorLine = $error['line'];
            $errorMessage = $error['message'];
            $errorType = $error['type'];
            $message = 'An error while processing your request. Please try again later.';

            if ($this->displayErrorDetails) {
                switch ($errorType) {
                    case E_USER_ERROR:
                        $message = "FATAL ERROR: {$errorMessage}. ";
                        $message .= " on line {$errorLine} in file {$errorFile}.";
                        break;

                    case E_USER_WARNING:
                        $message = "WARNING: {$errorMessage}";
                        break;

                    case E_USER_NOTICE:
                        $message = "NOTICE: {$errorMessage}";
                        break;

                    default:
                        $message = "ERROR: {$errorMessage}";
                        $message .= " on line {$errorLine} in file {$errorFile}.";
                        break;
                }
            }

            $exception = new HttpInternalServerErrorException($this->request, $message);
            $response = $this->errorHandler->__invoke(
                $this->request,
                $exception,
                $this->displayErrorDetails,
                false,
                false,
            );

            // $this->sendNotification($error);

            $responseEmitter = new ResponseEmitter();
            $responseEmitter->emit($response);
        }
    }


    // public function sendNotification($error) {
    //     $slack = new \Api\Mirasee\Slack\Message;
    //     $id = time();
    //     $url = $this->request->getUri();
    //     $msg = array(
    //         "text" => $error['message'],
    //         "blocks" => array(
    //             array(
    //                 "type" => "section",
    //                 "text" => array(
    //                     "type"=> "mrkdwn",
    //                     "text" => $this->getErrorName($error['type']) .' : '.$error['message'],
    //                 )
    //             ),
    //             array(
    //                 "type" => "section",
    //                 "block_id" => "Request".$id,
    //                 "text" => array(
    //                 "type"=> "mrkdwn",
    //                 "text" => $this->request->getMethod()." *Request:* ".$url->getScheme()."://".$url->getHost().$url->getPath(),
    //                 )
    //             ),
    //             array(
    //                 "type" => "section",
    //                 "block_id" => "file".$id,
    //                 "text" => array(
    //                 "type"=> "mrkdwn",
    //                 "text" => "*File:* ".$error['file'],
    //                 )
    //             ),
    //             array(
    //                 "type" => "section",
    //                 "block_id" => "line".$id,
    //                 "text" => array(
    //                     "type"=> "mrkdwn",
    //                     "text" => "*Line:* ".$error['line'],
    //                 )
    //             )
    //         )
    //     );

    //     $return = $slack->sendIntegrationError($msg);
    //     return true;
    // }

    public function getErrorName($code)
    {
        static $error_names = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];
        return $error_names[$code] ?? '';
    }

}
