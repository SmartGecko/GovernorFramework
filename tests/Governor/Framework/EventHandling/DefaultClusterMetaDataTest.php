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

namespace Governor\Framework\EventHandling;

/**
 * Description of DefaultClusterMetaDataTest
 *
 * @author david
 */
class DefaultClusterMetaDataTest extends \PHPUnit_Framework_TestCase
{

    private $config;

    public function setUp()
    {
        $this->config = new DefaultClusterMetaData();
    }

    public function testStoreNullValue()
    {
        $this->config->setProperty("null", null);

        $this->assertNull($this->config->getProperty("null"));
    }

    public function testStoreAndDelete()
    {
        $this->assertFalse($this->config->isPropertySet("key"));

        $this->config->setProperty("key", "value");
        $this->assertTrue($this->config->isPropertySet("key"));

        $this->config->setProperty("key", null);
        $this->assertTrue($this->config->isPropertySet("key"));

        $this->config->removeProperty("key");
        $this->assertFalse($this->config->isPropertySet("key"));
    }

    public function testStoreAndGet()
    {
        $this->assertNull($this->config->getProperty("key"));
        $value = new \stdClass();
        $this->config->setProperty("key", $value);
        $this->assertSame($value, $this->config->getProperty("key"));
    }

    public function testOverwriteProperties()
    {
        $this->assertNull($this->config->getProperty("key"));
        $value1 = "value1";
        $this->config->setProperty("key", $value1);
        $this->assertSame($value1, $this->config->getProperty("key"));

        $value2 = "value2";
        $this->config->setProperty("key", $value2);
        $this->assertSame($value2, $this->config->getProperty("key"));
    }

}
