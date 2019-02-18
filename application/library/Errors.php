<?php

namespace library;

class Errors
{
    /**
     * 注册异常处理
     * @return void
     */
    public static function register()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', FALSE);
        set_error_handler([__CLASS__, 'appError']);
        set_exception_handler([__CLASS__, 'appException']);
        register_shutdown_function([__CLASS__, 'appShutdown']);
    }
    
    /**
     * Error Handler
     * @param  integer $errno   错误编号
     * @param  integer $errstr  详细错误信息
     * @param  string  $errfile 出错的文件
     * @param  integer $errline 出错行号
     * @param array    $errcontext
     * @throws ErrorException
     */
    public static function appError($errno, $errstr, $errfile = '', $errline = 0, $errcontext = [])
    {
        $exception = new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        if (error_reporting() & $errno) {
            if (self::isFatal($errno)) {
                throw $exception;
            }
        }
    }

    /**
     * Exception Handler
     * @param  \Exception|\Throwable $e
     */
    public static function appException($e)
    {
        $message = str_conver($e->getMessage());
        \library\DebugLog::_log([
            'message' => $message,
            'file' => concat($e->getFile(), '(', $e->getLine(), ')'),
            'trace' => $e->getTraceAsString()
        ], 'exception');
        json(null, $message, -1);
    }

    /**
     * Shutdown Handler
     */
    public static function appShutdown()
    {
        if (!is_null($error = error_get_last()) && self::isFatal($error['type'])) {
            $exception = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            self::appException($exception);
        }
        \library\DebugLog::_show();
    }

    /**
     * 确定错误类型是否致命
     *
     * @param  int $type
     * @return bool
     */
    protected static function isFatal($type)
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }

}
