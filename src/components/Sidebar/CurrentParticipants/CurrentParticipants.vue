<!--
  - @copyright Copyright (c) 2019 Joas Schilling <coding@schilljs.com>
  -
  - @author Joas Schilling <coding@schilljs.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
-->

<template>
	<ParticipantsList :participants-list="participantsList" />
</template>

<script>

import ParticipantsList from '../ParticipantsList/ParticipantsList'
import { fetchParticipants } from '../../../services/participantsService'
import { EventBus } from '../../../services/EventBus'
import { PARTICIPANT } from '../../../constants'

export default {
	name: 'CurrentParticipants',

	components: {
		ParticipantsList,
	},

	computed: {
		token() {
			return this.$route.params.token
		},

		/**
		 * Gets the participants array.
		 *
		 * @returns {array}
		 */
		participantsList() {
			const participants = this.$store.getters.participantsList(this.token)

			return participants.slice().sort(this.sortParticipants)
		},
	},

	/**
	 * Fetches the messages when the MessageList created. The router mounts this
	 * component only if the token is passed in so there's no need to check the
	 * token prop.
	 */
	created() {
		this.onRouteChange()

		/**
		 * Add a listener for routeChange event emitted by the App.vue component.
		 * Call the onRouteChange method function whenever the route changes.
		 */
		EventBus.$on('routeChange', () => {
			this.$nextTick(() => {
				this.onRouteChange()
			})
		})
	},

	methods: {
		onRouteChange() {
			this.getParticipants()
		},

		/**
		 * Sort two participants by:
		 * - type (moderators before normal participants)
		 * - online status
		 * - display name
		 *
		 * @param {object} participant1 First participant
		 * @param {int} participant1.participantType First participant type
		 * @param {string} participant1.sessionId First participant session
		 * @param {string} participant1.displayName First participant display name
		 * @param {object} participant2 Second participant
		 * @param {int} participant2.participantType Second participant type
		 * @param {string} participant2.sessionId Second participant session
		 * @param {string} participant2.displayName Second participant display name
		 * @returns {number}
		 */
		sortParticipants(participant1, participant2) {
			const moderatorTypes = [PARTICIPANT.TYPE.OWNER, PARTICIPANT.TYPE.MODERATOR, PARTICIPANT.TYPE.GUEST_MODERATOR]
			const moderator1 = moderatorTypes.indexOf(participant1.participantType) !== -1
			const moderator2 = moderatorTypes.indexOf(participant2.participantType) !== -1

			if (moderator1 !== moderator2) {
				return moderator1 ? -1 : 1
			}

			if (participant1.sessionId === '0') {
				if (participant2.sessionId !== '0') {
					return 1
				}
			} else if (participant2.sessionId === '0') {
				return -1
			}

			return participant2.displayName - participant1.displayName
		},

		async getParticipants() {
			const participants = await fetchParticipants(this.token)
			this.$store.dispatch('purgeParticipantsStore', this.token)
			participants.data.ocs.data.forEach(participant => {
				this.$store.dispatch('addParticipant', {
					token: this.token,
					participant: participant,
				})
			})
		},
	},
}
</script>

<style scoped>

</style>
