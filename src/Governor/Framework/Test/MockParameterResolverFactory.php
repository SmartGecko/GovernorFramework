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

use Governor\Framework\Common\AbstractParameterResolverFactory;
use Governor\Framework\Common\FixedValueParameterResolver;
use Governor\Framework\Annotations as Governor;

/**
 * Description of ContainerParameterResolverFactory
 *
 * @author david
 */
class MockParameterResolverFactory extends AbstractParameterResolverFactory
{

    /**
     * @var array
     */
    private $services;

    function __construct($services)
    {
        $this->services = $services;
    }

    public function createInstance(array $methodAnnotations,
            \ReflectionParameter $parameter)
    {        
        $resolver = $this->getResolverFor($methodAnnotations, $parameter);        

        if ($resolver && $resolver instanceof Governor\Inject) {
            $service = $this->services[$resolver->service];
            
            return new FixedValueParameterResolver($service);
        }

        return null;
    }

}
