<?php

namespace Itinysun\Laraman\Traits;

use Illuminate\Http\Request;

trait HasRefreshTelescope
{

    /**
     * 重置telescope的状态
     * telescope会缓存是否记录请求的判断结果，需要每次判断是否需要记录本次请求
     * @param $request
     * @return void
     */
    protected function refreshTelescope($request): void
    {
        if (! config('telescope.enabled')) {
            return;
        }
        if(static::requestIsToApprovedDomain($request) &&
            static::requestIsToApprovedUri($request)){
            \Laravel\Telescope\Telescope::startRecording($loadMonitoredTags = false);
        }else{
            \Laravel\Telescope\Telescope::stopRecording();
        }
    }

    /**
     * 提取自telescope
     * @param $request
     * @return bool
     */
    protected static function requestIsToApprovedDomain($request): bool
    {
        return is_null(config('telescope.domain')) ||
            config('telescope.domain') !== $request->getHost();
    }

    /**
     * 提取自telescope，判断请求是否需要记录
     * Determine if the request is to an approved URI.
     *
     * @param Request $request
     * @return bool
     */
    protected static function requestIsToApprovedUri(Request $request): bool
    {
        if (! empty($only = config('telescope.only_paths', []))) {

            return $request->is($only);
        }

        return ! $request->is(
            collect([
                'telescope-api*',
                'vendor/telescope*',
                (config('horizon.path') ?? 'horizon').'*',
                'vendor/horizon*',
            ])
                ->merge(config('telescope.ignore_paths', []))
                ->unless(is_null(config('telescope.path')), function ($paths) {
                    return $paths->prepend(config('telescope.path').'*');
                })
                ->all()
        );
    }
}
