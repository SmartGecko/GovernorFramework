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

namespace Governor\Framework\CommandHandling\Gateway;

use Governor\Framework\Domain\MetaData;
use Governor\Framework\CommandHandling\CommandCallbackInterface;
use Governor\Framework\CommandHandling\CommandBusInterface;
use Governor\Framework\CommandHandling\GenericCommandMessage;
use Governor\Framework\CommandHandling\Callbacks\ResultCallback;

/**
 * Description of AbstractCommandGateway
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
abstract class AbstractCommandGateway implements CommandGatewayInterface
{

    /**     
     * @var CommandBusInterface
     */
    private $commandBus;

    public function __construct(CommandBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * {@inheritDoc}
     */
    public function send($command, CommandCallbackInterface $callback = null,
            MetaData $metaData = null)
    {
        $metaData = isset($metaData) ? $metaData : MetaData::emptyInstance();
        $message = new GenericCommandMessage($command, $metaData);

        $this->commandBus->dispatch($message, $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function sendAndWait($command, MetaData $metaData = null)
    {
        $metaData = isset($metaData) ? $metaData : MetaData::emptyInstance();
        $message = new GenericCommandMessage($command, $metaData);
        $callback = new ResultCallback();

        $this->commandBus->dispatch($message, $callback);

        return $callback->getResult();
    }

}
