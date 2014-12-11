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

namespace Governor\Tests\Correlation;

use Governor\Framework\Domain\MetaData;
use Governor\Framework\Domain\GenericMessage;
use Governor\Framework\Correlation\SimpleCorrelationDataProvider;
/**
 * Description of SimpleCorrelationDataProviderTest
 *
 * @author david
 */
class SimpleCorrelationDataProviderTest extends \PHPUnit_Framework_TestCase
{
    
    public function testResolveCorrelationData() 
    {
        $metaData = array(
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3'
        );
        
        $payload = new \stdClass();        
        $message = new GenericMessage($payload, new MetaData($metaData));

        $provider1 = new SimpleCorrelationDataProvider(array("key1"));
                
        $this->assertEquals(array("key1" => "value1"), $provider1->correlationDataFor($message));

        $provider2 = new SimpleCorrelationDataProvider(array("key1", "key2", "noExist", null));
        $actual2 = $provider2->correlationDataFor($message);
                
        $this->assertEquals("value1", $actual2["key1"]);
        $this->assertEquals("value2", $actual2["key2"]);
    }
}
