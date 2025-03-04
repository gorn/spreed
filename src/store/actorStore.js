/**
 * @copyright Copyright (c) 2019 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * This store helps to identify a the current actor in all cases.
 * In Talk not every user is a local nextcloud user, so identifying
 * solely by userId is not enough.
 * If an as no userId, they are a guest and identified by actorType + sessionId.
 */

import sha1 from 'crypto-js/sha1'
import { PARTICIPANT } from '../constants'

const state = {
	userId: null,
	sessionId: null,
	sessionHash: null,
	actorId: null,
	actorType: null,
	displayName: '',
}

const getters = {
	getUserId: (state) => () => {
		return state.userId
	},
	getSessionId: (state) => () => {
		return state.sessionId
	},
	getSessionHash: (state) => () => {
		return state.sessionHash
	},
	getActorId: (state) => () => {
		return state.actorId
	},
	getActorType: (state) => () => {
		return state.actorType
	},
	getDisplayName: (state) => () => {
		return state.displayName
	},
}

const mutations = {
	/**
	 * Set the userId
	 *
	 * @param {object} state current store state;
	 * @param {string} userId The user id
	 */
	setUserId(state, userId) {
		state.userId = userId
		state.actorId = userId
	},
	/**
	 * Set the sessionId
	 *
	 * @param {object} state current store state;
	 * @param {string} sessionId The actors session id
	 */
	setSessionId(state, sessionId) {
		state.sessionId = sessionId
		state.sessionHash = sha1(sessionId)
	},
	/**
	 * Set the actorId
	 *
	 * @param {object} state current store state;
	 * @param {string} actorId The actor id
	 */
	setActorId(state, actorId) {
		state.actorId = actorId
	},
	/**
	 * Set the userId
	 *
	 * @param {object} state current store state;
	 * @param {string} displayName The name
	 */
	setDisplayName(state, displayName) {
		state.displayName = displayName
	},
	/**
	 * Set the userId
	 *
	 * @param {object} state current store state;
	 * @param {actorType} actorType The actor type of the user
	 */
	setActorType(state, actorType) {
		state.actorType = actorType
	},
}

const actions = {

	/**
	 * Set the actor from the current user
	 *
	 * @param {object} context default store context;
	 * @param {object} user A NextcloudUser object as returned by @nextcloud/auth
	 * @param {string} user.uid The user id of the user
	 * @param {string|null} user.displayName The display name of the user
	 */
	setCurrentUser(context, user) {
		context.commit('setUserId', user.uid)
		context.commit('setDisplayName', user.displayName || user.uid)
		context.commit('setActorType', 'users')
	},

	/**
	 * Set the actor from the current participant
	 *
	 * @param {object} context default store context;
	 * @param {object} participant The participant data
	 * @param {int} participant.participantType The type of the participant
	 * @param {string} participant.sessionId The session id of the participant
	 */
	setCurrentParticipant(context, participant) {
		context.commit('setSessionId', participant.sessionId)

		if (participant.participantType === PARTICIPANT.TYPE.GUEST
			|| participant.participantType === PARTICIPANT.TYPE.GUEST_MODERATOR) {
			context.commit('setUserId', null)
			context.commit('setActorType', 'guests')
			context.commit('setActorId', 'guest/' + context.getters.getSessionHash())
			// FIXME context.commit('setDisplayName', '')
		}
	},
}

export default { state, mutations, getters, actions }
