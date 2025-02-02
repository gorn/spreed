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
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -
 -->

<template>
	<div id="general_settings" class="videocalls section">
		<h2>{{ t('spreed', 'General settings') }}</h2>

		<p>
			<label for="start_calls">{{ t('spreed', 'Start calls') }}</label>
			<Multiselect id="start_calls"
				v-model="startCalls"
				:options="startCallOptions"
				:placeholder="t('spreed', 'Who can start a call?')"
				label="label"
				track-by="value"
				:disabled="loading || loadingStartCalls"
				@input="saveStartCalls" />
		</p>
		<p>
			<em>{{ t('spreed', 'When a call has started, everyone with access to the conversation can join the call.') }}</em>
		</p>

		<h3>{{ t('spreed', 'Integration into other apps') }}</h3>

		<p>
			<input id="conversations_files"
				v-model="conversationsFiles"
				type="checkbox"
				name="conversations_files"
				class="checkbox"
				:disabled="loading || loadingConversationsFiles"
				@change="saveConversationsFiles">
			<label for="conversations_files">{{ t('spreed', 'Allow conversations on files') }}</label>
		</p>

		<p>
			<input id="conversations_files_public_shares"
				v-model="conversationsFilesPublicShares"
				type="checkbox"
				name="conversations_files_public_shares"
				class="checkbox"
				:disabled="loading || loadingConversationsFiles || !conversationsFiles"
				@change="saveConversationsFilesPublicShares">
			<label for="conversations_files_public_shares">{{ t('spreed', 'Allow conversations on public shares for files') }}</label>
		</p>
	</div>
</template>

<script>
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'

const startCallOptions = [
	{ value: 0, label: t('spreed', 'Everyone') },
	{ value: 1, label: t('spreed', 'Users and moderators') },
	{ value: 2, label: t('spreed', 'Moderators only') },
]
export default {
	name: 'GeneralSettings',

	components: {
		Multiselect,
	},

	data() {
		return {
			loading: true,
			loadingStartCalls: false,
			loadingConversationsFiles: false,

			startCallOptions,
			startCalls: startCallOptions[0],

			conversationsFiles: true,
			conversationsFilesPublicShares: true,
		}
	},

	mounted() {
		this.loading = true
		this.startCalls = startCallOptions[parseInt(OCP.InitialState.loadState('talk', 'start_calls'))]
		this.conversationsFiles = parseInt(OCP.InitialState.loadState('talk', 'conversations_files')) === 1
		this.conversationsFilesPublicShares = parseInt(OCP.InitialState.loadState('talk', 'conversations_files_public_shares')) === 1
		this.loading = false
	},

	methods: {
		saveStartCalls() {
			this.loadingStartCalls = true

			OCP.AppConfig.setValue('spreed', 'start_calls', this.startCalls.value, {
				success: function() {
					this.loadingStartCalls = false
				}.bind(this),
			})
		},
		saveConversationsFiles() {
			this.loadingConversationsFiles = true

			OCP.AppConfig.setValue('spreed', 'conversations_files', this.conversationsFiles ? '1' : '0', {
				success: function() {
					if (!this.conversationsFiles) {
						// When the file integration is disabled, the share integration is also disabled
						OCP.AppConfig.setValue('spreed', 'conversations_files_public_shares', '0', {
							success: function() {
								this.conversationsFilesPublicShares = false
								this.loadingConversationsFiles = false
							}.bind(this),
						})
					} else {
						this.loadingConversationsFiles = false
					}
				}.bind(this),
			})
		},
		saveConversationsFilesPublicShares() {
			this.loadingConversationsFiles = true

			OCP.AppConfig.setValue('spreed', 'conversations_files_public_shares', this.conversationsFilesPublicShares ? '1' : '0', {
				success: function() {
					this.loadingConversationsFiles = false
				}.bind(this),
			})
		},
	},
}
</script>
<style scoped lang="scss">
p {
	display: flex;
	align-items: center;

	label {
		display: block;
		margin-right: 10px;
	}
}

.multiselect {
	flex-grow: 1;
	max-width: 300px;
}
</style>
