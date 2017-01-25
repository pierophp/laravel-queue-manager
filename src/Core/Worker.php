<?php

namespace LaravelQueueManager\Core;

use Exception;
use Throwable;
use Illuminate\Queue\Worker as LaravelWorker;
use Illuminate\Queue\WorkerOptions;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class Worker extends LaravelWorker
{
    /**
     * Process a given job from the queue.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     *
     * @throws \Throwable
     */
    public function process($connectionName, $job, WorkerOptions $options)
    {
        try {
            $this->raiseBeforeJobEvent($connectionName, $job);

            $this->markJobAsFailedIfAlreadyExceedsMaxAttempts(
                $connectionName, $job, (int) $options->maxTries
            );

            // Here we will fire off the job and let it process. We will catch any exceptions so
            // they can be reported to the developers logs, etc. Once the job is finished the
            // proper events will be fired to let any listeners know this job has finished.
            if (!config('queue_manager.execute_as_api')) {
                $job->fire();
            } else {
                $this->firePost($job);
            }

            $this->raiseAfterJobEvent($connectionName, $job);
        } catch (Exception $e) {
            $this->handleJobException($connectionName, $job, $options, $e);
        } catch (Throwable $e) {
            $this->handleJobException(
                $connectionName, $job, $options, new FatalThrowableError($e)
            );
        }
    }

    /**
     * Using curl to avoid conflict with Guzzle in project
     * @param $job
     * @throws Exception
     */
    protected function firePost($job)
    {
        $data = [
            'data' => $job->getRawBody(),
        ];

        $url = config('queue_manager.api_url');

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curlResponse = curl_exec($ch);

        $curlErrorCode = curl_errno($ch);

        curl_close($ch);

        if ($curlErrorCode) {
            throw new \Exception(curl_strerror($curlErrorCode), $curlErrorCode);
        }

        $response = json_decode($curlResponse);

        if ($response->status_code !== 200) {
            throw new \Exception($response->error_description, $response->error_code);
        }

        if (! $job->isDeletedOrReleased()) {
            $job->delete();
        }
    }

}