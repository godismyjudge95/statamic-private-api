<?php

namespace Tv2regionerne\StatamicPrivateApi\Traits;

use Facades\Statamic\API\FilterAuthorizer;
use Illuminate\Validation\ValidationException;

trait VerifiesPrivateAPI
{
    public function resourcesAllowed(string $type, string $key): bool
    {
        $resources = config('private-api.resources.'.$type);

        if (! $resources) {
            return false;
        }

        if ($resources === true) {
            return true;
        }

        if (is_array($resources)) {
            if (array_key_exists('allowed_filters', $resources)) {
                unset($resources['allowed_filters']);

                if (empty($resources)) {
                    return true;
                }
            }

            if (array_key_exists($key, $resources)) {
                return true;
            }
        }

        return false;
    }

    public function returnValidationErrors(ValidationException $e)
    {
        return response()->json([
            'error' => true,
            'errors' => $e->errors(),
        ], 422);
    }

    /**
     * Read allowed_filters from config/private-api.php. If it is not set there,
     * read config/statamic/api.php (the behaviour before this config existed).
     */
    protected function privateApiAllowedFilters(string $resource, ?string $handle = null): array
    {
        $key = collect(['private-api.resources', $resource, $handle, 'allowed_filters'])->filter()->join('.');

        if (config()->has($key)) {
            $config = config($key);

            return is_array($config) ? $config : [];
        }

        return $handle
            ? FilterAuthorizer::allowedForSubResources('api', $resource, $handle)
            : FilterAuthorizer::allowedForResource('api', $resource);
    }
}
