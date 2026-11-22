<?php

declare(strict_types=1);

if (! function_exists('withLocalHttpServer')) {
    /**
     * Run a callback against a throwaway local web server whose router answers a few fixed paths,
     * so the cURL code in `GeneratorUtils` runs for real without the network.
     *
     * - `/echo`: 200, a JSON object of the request's `User-Agent`, `Accept` and `X-Probe` headers.
     * - `/redirect`: 302 to `/echo`.
     * - `/missing`: 404 with the body `not found`.
     * - `/slow`: 200 after three seconds.
     *
     * The server runs as a single process (`PHP_CLI_SERVER_WORKERS` removed) so terminating it
     * ends it, and the child's streams are pipes because a mutation run replaces the file stream
     * wrapper, which a file descriptor spec cannot use.
     *
     * @template T
     *
     * @param  Closure(string): T  $callback  Receives the base URL, without a trailing slash.
     * @return T
     */
    function withLocalHttpServer(Closure $callback): mixed
    {
        $probe = stream_socket_server('tcp://127.0.0.1:0');
        if ($probe === false) {
            throw new RuntimeException('Could not reserve a local port.');
        }

        $port = (int) substr((string) strrchr((string) stream_socket_get_name($probe, false), ':'), 1);
        fclose($probe);

        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'local-http-'.bin2hex(random_bytes(6));
        mkdir($root);
        $router = $root.DIRECTORY_SEPARATOR.'router.php';
        file_put_contents($router, <<<'PHP_WRAP'
        <?php
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if ($path === '/echo') {
            header('Content-Type: application/json');
            echo json_encode([
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'accept' => $_SERVER['HTTP_ACCEPT'] ?? null,
                'probe' => $_SERVER['HTTP_X_PROBE'] ?? null,
            ]);
        } elseif ($path === '/redirect') {
            header('Location: /echo', true, 302);
        } elseif ($path === '/missing') {
            http_response_code(404);
            echo 'not found';
        } elseif ($path === '/slow') {
            sleep(3);
            echo 'late';
        }
        return true;
        PHP_WRAP);

        $environment = array_filter(getenv(), static fn (string $name): bool => $name !== 'PHP_CLI_SERVER_WORKERS', ARRAY_FILTER_USE_KEY);
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:'.$port, '-t', $root, $router],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            $environment
        );

        try {
            if (! is_resource($process)) {
                throw new RuntimeException('The local web server could not be started.');
            }

            $deadline = microtime(true) + 5;
            set_error_handler(static fn (): bool => true);
            try {
                while (($socket = fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2)) === false) {
                    if (microtime(true) > $deadline) {
                        throw new RuntimeException('The local web server did not start.');
                    }

                    usleep(50_000);
                }
            } finally {
                restore_error_handler();
            }

            fclose($socket);

            return $callback('http://127.0.0.1:'.$port);
        } finally {
            if (is_resource($process)) {
                proc_terminate($process);
                foreach ($pipes as $pipe) {
                    fclose($pipe);
                }

                proc_close($process);
            }

            if (is_file($router)) {
                unlink($router);
            }

            rmdir($root);
        }
    }
}
