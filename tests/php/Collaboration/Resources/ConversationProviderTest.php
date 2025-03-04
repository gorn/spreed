<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Talk\Tests\php\Collaboration\Resources;

use OCA\Talk\Collaboration\Collaborators\RoomPlugin;
use OCA\Talk\Collaboration\Resources\ConversationProvider;
use OCA\Talk\Exceptions\ParticipantNotFoundException;
use OCA\Talk\Exceptions\RoomNotFoundException;
use OCA\Talk\Manager;
use OCA\Talk\Participant;
use OCA\Talk\Room;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Resources\IResource;
use OCP\Collaboration\Resources\ResourceException;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ConversationProviderTest extends TestCase {

	/** @var Manager|MockObject */
	protected $manager;
	/** @var IUserSession|MockObject */
	protected $userSession;
	/** @var IURLGenerator|MockObject */
	protected $urlGenerator;
	/** @var ConversationProvider */
	protected $provider;

	public function setUp() {
		parent::setUp();

		$this->manager = $this->createMock(Manager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->provider = new ConversationProvider(
			$this->manager,
			$this->userSession,
			$this->urlGenerator
		);
	}

	public function testCanAccessResourceThrowsGuest(): void {
		$resource = $this->createMock(IResource::class);

		$this->expectException(ResourceException::class);
		$this->expectExceptionMessage('Guests are not supported at the moment');
		$this->provider->canAccessResource($resource, null);
	}

	public function testCanAccessResourceThrowsRoom(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('uid');
		$resource = $this->createMock(IResource::class);
		$resource->expects($this->once())
			->method('getId')
			->willReturn('token');

		$this->manager->expects($this->once())
			->method('getRoomForParticipantByToken')
			->with('token', 'uid')
			->willThrowException(new RoomNotFoundException());

		$this->expectExceptionMessage('Conversation not found');
		$this->provider->canAccessResource($resource, $user);
	}

	public function testCanAccessResourceThrowsParticipant(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('uid');
		$resource = $this->createMock(IResource::class);
		$resource->expects($this->once())
			->method('getId')
			->willReturn('token');
		$room = $this->createMock(Room::class);
		$room->expects($this->once())
			->method('getParticipant')
			->with('uid')
			->willThrowException(new ParticipantNotFoundException());

		$this->manager->expects($this->once())
			->method('getRoomForParticipantByToken')
			->with('token', 'uid')
			->willReturn($room);

		$this->expectExceptionMessage('Participant not found');
		$this->provider->canAccessResource($resource, $user);
	}

	public function testCanAccessResourceParticipantNotAdded(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('uid');
		$resource = $this->createMock(IResource::class);
		$resource->expects($this->once())
			->method('getId')
			->willReturn('token');

		$participant = $this->createMock(Participant::class);
		$participant->expects($this->once())
			->method('getParticipantType')
			->willReturn(Participant::USER_SELF_JOINED);
		$room = $this->createMock(Room::class);
		$room->expects($this->once())
			->method('getParticipant')
			->with('uid')
			->willReturn($participant);

		$this->manager->expects($this->once())
			->method('getRoomForParticipantByToken')
			->with('token', 'uid')
			->willReturn($room);

		$this->assertFalse($this->provider->canAccessResource($resource, $user));
	}

	public function dataCanAccessResourceYes(): array {
		return [
			[Participant::OWNER],
			[Participant::MODERATOR],
			[Participant::USER],
		];
	}

	/**
	 * @dataProvider dataCanAccessResourceYes
	 * @param int $participantType
	 */
	public function testCanAccessResourceYes(int $participantType): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('uid');
		$resource = $this->createMock(IResource::class);
		$resource->expects($this->once())
			->method('getId')
			->willReturn('token');

		$participant = $this->createMock(Participant::class);
		$participant->expects($this->once())
			->method('getParticipantType')
			->willReturn($participantType);
		$room = $this->createMock(Room::class);
		$room->expects($this->once())
			->method('getParticipant')
			->with('uid')
			->willReturn($participant);

		$this->manager->expects($this->once())
			->method('getRoomForParticipantByToken')
			->with('token', 'uid')
			->willReturn($room);

		$this->assertTrue($this->provider->canAccessResource($resource, $user));
	}
}
