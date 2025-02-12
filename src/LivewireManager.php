<?php

namespace Livewire;

use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;
use Livewire\Mechanisms\HandleRequests\HandleRequests;
use Livewire\Mechanisms\HandleComponents\HandleComponents;
use Livewire\Mechanisms\HandleComponents\ComponentContext;
use Livewire\Mechanisms\FrontendAssets\FrontendAssets;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Livewire\Mechanisms\ComponentRegistry;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Features\SupportTesting\DuskTestable;
use Livewire\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets;

class LivewireManager
{
    protected LivewireServiceProvider $provider;

    function setProvider(LivewireServiceProvider $provider)
    {
        $this->provider = $provider;
    }

    function provide($callback)
    {
        \Closure::bind($callback, $this->provider, $this->provider::class)();
    }

    function component($name, $class = null)
    {
        app(ComponentRegistry::class)->component($name, $class);
    }

    function componentHook($hook)
    {
        ComponentHookRegistry::register($hook);
    }

    function propertySynthesizer($synth)
    {
        app(HandleComponents::class)->registerPropertySynthesizer($synth);
    }

    function directive($name, $callback)
    {
        app(ExtendBlade::class)->livewireOnlyDirective($name, $callback);
    }

    function precompiler($pattern, $callback)
    {
        app(ExtendBlade::class)->livewireOnlyPrecompiler($pattern, $callback);
    }

    function new($name, $id = null)
    {
        return app(ComponentRegistry::class)->new($name, $id);
    }

    function resolveMissingComponent($resolver)
    {
        return app(ComponentRegistry::class)->resolveMissingComponent($resolver);
    }

    function mount($name, $params = [], $key = null)
    {
        return app(HandleComponents::class)->mount($name, $params, $key);
    }

    function snapshot($component)
    {
        return app(HandleComponents::class)->snapshot($component);
    }

    function fromSnapshot($snapshot)
    {
        return app(HandleComponents::class)->fromSnapshot($snapshot);
    }

    function listen($eventName, $callback) {
        return on($eventName, $callback);
    }

    function current()
    {
        return last(app(HandleComponents::class)::$componentStack);
    }

    function update($snapshot, $diff, $calls)
    {
        return app(HandleComponents::class)->update($snapshot, $diff, $calls);
    }

    function updateProperty($component, $path, $value)
    {
        $dummyContext = new ComponentContext($component, false);

        return app(HandleComponents::class)->updateProperty($component, $path, $value, $dummyContext);
    }

    function isLivewireRequest()
    {
        return app(HandleRequests::class)->isLivewireRequest();
    }

    function componentHasBeenRendered()
    {
        return SupportAutoInjectedAssets::$hasRenderedAComponentThisRequest;
    }

    function forceAssetInjection()
    {
        SupportAutoInjectedAssets::$forceAssetInjection = true;
    }

    function setUpdateRoute($callback)
    {
        return app(HandleRequests::class)->setUpdateRoute($callback);
    }

    function getUpdateUri()
    {
        return app(HandleRequests::class)->getUpdateUri();
    }

    function setScriptRoute($callback)
    {
        return app(FrontendAssets::class)->setScriptRoute($callback);
    }

    function useScriptTagAttributes($attributes)
    {
        return app(FrontendAssets::class)->useScriptTagAttributes($attributes);
    }

    protected $queryParamsForTesting = [];

    function withQueryParams($params)
    {
        $this->queryParamsForTesting = $params;

        return $this;
    }

    function test($name, $params = [])
    {
        return Testable::create($name, $params, $this->queryParamsForTesting);
    }

    function visit($name)
    {
        return DuskTestable::create($name, $params = [], $this->queryParamsForTesting);
    }

    function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $driver = null)
    {
         Testable::actingAs($user, $driver);

         return $this;
    }

    function isRunningServerless()
    {
        return in_array($_ENV['SERVER_SOFTWARE'] ?? null, [
            'vapor',
            'bref',
        ]);
    }

    function addPersistentMiddleware($middleware)
    {
        app(PersistentMiddleware::class)->addPersistentMiddleware($middleware);
    }

    function setPersistentMiddleware($middleware)
    {
        app(PersistentMiddleware::class)->setPersistentMiddleware($middleware);
    }

    function getPersistentMiddleware()
    {
        return app(PersistentMiddleware::class)->getPersistentMiddleware();
    }

    function flushState()
    {
        trigger('flush-state');
    }

    protected $jsFeatures = [];

    function enableJsFeature($name)
    {
        $this->jsFeatures[] = $name;
    }

    function getJsFeatures()
    {
        return $this->jsFeatures;
    }

    function originalUrl()
    {
        if ($this->isLivewireRequest()) {
            return url()->to($this->originalPath());
        }

        return url()->current();
    }

    function originalPath()
    {
        if ($this->isLivewireRequest()) {
            $snapshot = json_decode(request('components.0.snapshot'), true);

            return data_get($snapshot, 'memo.path', 'POST');
        }

        return request()->path();
    }

    function originalMethod()
    {
        if ($this->isLivewireRequest()) {
            $snapshot = json_decode(request('components.0.snapshot'), true);

            return data_get($snapshot, 'memo.method', 'POST');
        }

        return request()->method();
    }
}
