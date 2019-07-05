<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================================ */

namespace Opis\Colibri;

use function Opis\Colibri\Functions\session;

class Alerts extends View
{
    /** @var bool */
    protected $dismissible = false;

    /** @var string */
    protected $prefix = 'opis_colibri_alert_';

    /**
     * Alerts constructor.
     */
    public function __construct()
    {
        parent::__construct('alerts');

        $this->vars = null;
    }

    /**
     * @param string $type
     * @param string $message
     * @return Alerts
     */
    protected function alert(string $type, string $message): self
    {
        $type = $this->prefix . $type;
        $list = session()->flash()->get($type, []);
        $list[] = $message;
        session()->flash()->set($type, $list);

        return $this;
    }

    /**
     * @return array
     */
    public function viewVariables(): array
    {
        if ($this->vars === null) {
            $this->vars = [
                'dismissible' => $this->dismissible,
                'has_alerts' => $this->hasAlerts(),
            ];
            $flash = session()->flash();
            foreach (['error', 'warning', 'success', 'info'] as $key) {
                $type = $this->prefix . $key;
                $this->vars[$key] = $flash->get($type, []);
                $flash->delete($type);
            }
        }

        return $this->vars;
    }

    /**
     * @return bool
     */
    protected function hasAlerts(): bool
    {
        return ($this->hasErrors() || $this->hasMessages() || $this->hasWarnings() || $this->hasInfo());
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return session()->flash()->has($this->prefix . 'error');
    }

    /**
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return session()->flash()->has($this->prefix . 'warning');
    }

    /**
     * @return bool
     */
    public function hasInfo(): bool
    {
        return session()->flash()->has($this->prefix . 'info');
    }

    /**
     * @return bool
     */
    public function hasMessages(): bool
    {
        return session()->flash()->has($this->prefix . 'success');
    }

    /**
     * @param bool $value
     * @return Alerts
     */
    public function dismissible(bool $value = true): self
    {
        $this->dismissible = $value;
        return $this;
    }

    /**
     * @param string $message
     * @return Alerts
     */
    public function error(string $message): self
    {
        return $this->alert('error', $message);
    }

    /**
     * @param string $message
     * @return Alerts
     */
    public function warning(string $message): self
    {
        return $this->alert('warning', $message);
    }

    /**
     * @param string $message
     * @return Alerts
     */
    public function success(string $message): self
    {
        return $this->alert('success', $message);
    }

    /**
     * @param string $message
     * @return Alerts
     */
    public function info(string $message): self
    {
        return $this->alert('info', $message);
    }
}