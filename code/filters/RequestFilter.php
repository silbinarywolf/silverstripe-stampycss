<?php

namespace Stampy;
use Config;
use Debug;
use Controller;

class RequestFilter implements \RequestFilter
{
    public function preRequest(\SS_HTTPRequest $request, \Session $session, \DataModel $model)
    {
  
    }

    public function postRequest(\SS_HTTPRequest $request, \SS_HTTPResponse $response, \DataModel $model)
    {
        // Write to the cache if we made it to the end of the request.
        // NOTE(Jake): This is not called by DynamicCache if the cache has a hit.
        $cssCrush = singleton('Stampy\CSSCrush');
        $cssCrush->writeToCache();
    }
}
