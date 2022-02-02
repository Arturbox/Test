<?php

namespace App\Exceptions;

use App\Tools\APIResponse;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Handler
 * @package App\Exceptions
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * @param Throwable $exception
     * @throws Exception|Throwable
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param $request
     * @param Throwable $exception
     * @return Response|JsonResponse
     */
    public function render($request, Throwable $exception)
    {
        if ($request->isJson()) {
            return $this->handlerAPIErrors($exception);
        }

        return $this->handleAppErrors($exception, $request);
    }

    /**
     * @param $exception
     * @return JsonResponse
     */
    public function handlerAPIErrors($exception): JsonResponse
    {
        $exceptionName = get_class($exception);

        switch ($exceptionName) {
            case ValidationException::class:
                $response['message'] = trans("errors.".getClassName($exception));
                $response['errors'] = $exception->errors();
                $response['status'] = \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY;
                break;
            case AuthorizationException::class:
            case UnauthorizedException::class:
                $response['message'] = trans("errors.".getClassName($exception));
                $response['status'] = Response::HTTP_FORBIDDEN;
                break;
            case BadRequestException::class:
                $response['message'] = trans("errors.".getClassName($exception));
                $response['status'] = Response::HTTP_BAD_REQUEST;
                break;
            case AuthenticationException::class:
                $response['message'] = trans("errors.".getClassName($exception));
                $response['status'] = Response::HTTP_UNAUTHORIZED;
                break;
            case ModelNotFoundException::class:
            case NotFoundHttpException::class:
                $response['message'] = trans("errors.".getClassName($exception));
                $response['status'] = Response::HTTP_NOT_FOUND;
                break;
            case MethodNotAllowedHttpException::class:
                $response['message'] = trans("errors.".getClassName($exception));
                $response['status'] = Response::HTTP_METHOD_NOT_ALLOWED;
                break;
            case ClientException::class:
                $response['message'] = trans("errors.".getClassName($exception));
                $response['status'] = $exception->getCode();
                break;
            default:
                $response['message'] =  trans("errors.default");
                $response['status'] = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
        }

        if (config('app.debug')) {
            $response['message'] = empty($exception->getMessage()) ? $response['message'] : $exception->getMessage();
        }

        return APIResponse::errorResponse($response, 'Error request.', $response['status']);
    }

    /**
     * @param $exception
     * @param $request
     * @return JsonResponse|RedirectResponse|Response
     */
    public function handleAppErrors($exception, $request)
    {
        switch ($exception) {
            case $exception instanceof HttpResponseException:
                return $exception->getResponse();
            case $exception instanceof AuthorizationException:
                return $this->unauthorized();
            case $exception instanceof AuthenticationException:
                return $this->unauthorizedFreelancer();
            case $exception instanceof ValidationException:
                return $this->convertValidationExceptionToResponse($exception, $request);
            default:
                return $this->prepareResponse($request, $exception);
        }
    }

    /**
     * @return RedirectResponse|JsonResponse
     */
    protected function unauthorized()
    {
        return redirect()->back();
    }

    /**
     * @return RedirectResponse|JsonResponse
     */
    protected function unauthorizedFreelancer()
    {
        return redirect('login');
    }


    /**
     * Create a response object from the given validation exception.
     *
     * @param  ValidationException  $e
     * @param  $request
     * @return Response|JsonResponse
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        return redirect()->back()->withInput($request->input())->withErrors($e->validator->errors()->getMessages());
    }

    /**
     * Prepare response containing exception render.
     *
     * @param  $request
     * @param Throwable $e
     * @return Response|JsonResponse
     */
    protected function prepareResponse( $request, Throwable $e)
    {
        if ($this->isHttpException($e)) {
            switch ($e->getStatusCode()) {
                case Response::HTTP_NOT_FOUND:
                case Response::HTTP_INTERNAL_SERVER_ERROR:
                case Response::HTTP_METHOD_NOT_ALLOWED:
                    return redirect('404');
                default:
                    return $this->renderHttpException($e);
            }
        }

        return $this->toIlluminateResponse($this->convertExceptionToResponse($e), $e);
    }
}
