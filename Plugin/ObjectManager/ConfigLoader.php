<?php
namespace MSP\DevTools\Plugin\ObjectManager;

use Magento\Framework\App\ObjectManager\ConfigLoader as FrameworkConfigLoader;
use Magento\Framework\App\StaticResource;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use function debug_backtrace;

class ConfigLoader
{
    /**
     * In Plugin\PhpEnvironment\ResponsePlugin we use Model\PageInfo.
     * In Model\PageInfo we use Magento\Framework\View\LayoutInterface.
     * Creating a Layout object which has a plugin (eg Plugin\View\LayoutPlugin)
     * will pull in instances of UrlInterface and ResolverInterface through
     * dependency injection. Both of these classes have preferences in the
     * adminhtml area to classes that instanciate an admin session object. When
     * this happens, a new admin session will be created if one does not already
     * exist.
     *
     * As the path to static resources does not match the path to the adminhtml
     * frontend route, no cookie identifying the current/previous admin session
     * is sent by the user agent. So, when the admin session object is
     * initialised, no existing session is found; a new session is therefore
     * created and sent to the user agent as part of this request. This replaces
     * the existing admin session for the user, seemingly logging them out.
     *
     * All of this logic only kicks in when a standard request object is used,
     * such as when an exception is thrown. When the static resource application
     * finds the file it is seeking, this follows a different code path than the
     * exception handling referenced above.
     *
     * @param FrameworkConfigLoader $subject
     * @param array $result
     * @return array
     */
    public function afterLoad(FrameworkConfigLoader $subject, array $result): array
    {
        if ($this->isStaticResourceApp()) {
            // These both pull in an admin session object
            unset($result['preferences'][ResolverInterface::class]);
            unset($result['preferences'][UrlInterface::class]);
        }
        return $result;
    }

    /**
     * Work out if the currently running instance of Magento\Framework\AppInterface
     * is a StaticResource or something else.
     *
     * @return bool
     */
    protected function isStaticResourceApp(): bool
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($trace as $frame) {
            if ($frame['class'] == StaticResource::class) {
                return true;
            }
        }
        return false;
    }
}
