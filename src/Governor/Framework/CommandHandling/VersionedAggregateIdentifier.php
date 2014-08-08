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

namespace Governor\Framework\CommandHandling;

/**
 * Description of VersionedAggregateIdentifier
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class VersionedAggregateIdentifier
{
    /**     
     * @var string
     */
    private $identifier;
    
    /**     
     * @var integer
     */
    private $version;

    /**
     * Initializes a VersionedAggregateIdentifier with the given {@code identifier} and {@code version}.
     *
     * @param string $identifier The identifier of the targeted aggregate
     * @param integer $version    The expected version of the targeted aggregate, or {@code null} if the version is irrelevant
     */
    public function __construct($identifier, $version)
    {
        $this->identifier = $identifier;
        $this->version = $version;
    }

    /**
     * Returns the identifier of the targeted Aggregate. May never return <code>null</code>.
     *
     * @return string the identifier of the targeted Aggregate
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Returns the version of the targeted Aggregate, or {@code null} if the version is irrelevant.
     *
     * @return integer the version of the targeted Aggregate
     */
    public function getVersion()
    {
        return $this->version;
    }

}
