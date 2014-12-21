<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The software is based on the Axon Framework project which is
 * licensed under the Apache 2.0 license. For more information on the Axon Framework
 * see <http://www.axonframework.org/>.
 * 
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.governor-framework.org/>.
 */

namespace Governor\Framework\Test;

use Governor\Framework\Common\ParameterResolverFactoryInterface;
use Governor\Framework\Common\AbstractParameterResolverFactory;
use Governor\Framework\Common\DefaultParameterResolverFactory;
use Governor\Framework\Common\FixedValueParameterResolver;
use Governor\Framework\Annotations as Governor;

/**
 * FixtureParameterResolverFactory implementation for the testing framework.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class FixtureParameterResolverFactory extends AbstractParameterResolverFactory
{

    /**
     * @var array
     */
    private $services;
  
    /**     
     * @var ParameterResolverFactoryInterface[]
     */
    private $delegates;

    /**
     *
     */
    function __construct()
    {
        $this->delegates = array(
            new DefaultParameterResolverFactory()
        );
    }

    /**
     * @param array $methodAnnotations
     * @param \ReflectionParameter $parameter
     * @return FixedValueParameterResolver|\Governor\Framework\Common\ParameterResolverInterface|null
     */
    public function createInstance(array $methodAnnotations,
            \ReflectionParameter $parameter)
    {
        foreach ($this->delegates as $delegate) {
            if (null !== $factory = $delegate->createInstance($methodAnnotations, $parameter)) {
                return $factory;
            }
        }

        $resolver = $this->getResolverFor($methodAnnotations, $parameter);

        if ($resolver && $resolver instanceof Governor\Inject) {
            $service = $this->services[$resolver->service];

            return new FixedValueParameterResolver($service);
        }

        return null;
    }

    /**
     * @param string $id
     * @param mixed $service
     */
    public function registerService($id, $service)
    {
        $this->services[$id] = $service;
    }

    /**
     *
     */
    public function clear()
    {
        $this->services = array();
    }
}
