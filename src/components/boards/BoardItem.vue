<!--
* @copyright Copyright (c) 2018 Michael Weimann <mail@michael-weimann.eu>
*
* @author Michael Weimann <mail@michael-weimann.eu>
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
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*
-->

<template>
	<v-collapse-wrapper  ref="collapse_content" class="bullet-area">
		<router-link :id="`board-${board.id}`"
			:title="board.title"
			:to="routeTo"
			class="board-list-row"
			tag="div">

			<div v-show="!editing" v-collapse-toggle @click.prevent.stop="toggleCollapse" >
				<div  v-if="!isFetching" class="board-list-bullet-cell"  >
					<div :style="{ 'background-color': `#${board.color}` }"  class="board-list-bullet bullet-color"/>
					<div :class="{'icon-triangle-e': !bulletOpen, 'icon-triangle-s': bulletOpen, 'bullet-caret': true}" />
				</div>
				<div v-else-if="isFetching" style="text-align: center; margin-left: 22px;">
					<div :class="{'icon-loading': isFetching}" />
				</div>
			</div>

			<div v-show="!editing"  class="board-list-title-cell">
				{{ board.title }} <a href="#" @click.prevent.stop="activeEditItem" v-show="canManage" class="edit-button">- Editar</a>
			</div>
		
			<div v-show="editing" class="board-list-title-cell board-edit">
				<ColorPicker class="app-navigation-entry-bullet-wrapper" :value="`#${board.color}`" @input="updateColor">
					<div :style="{ backgroundColor: getColor }" class="color0 icon-colorpicker app-navigation-entry-bullet" />
				</ColorPicker>
				<form @submit.prevent.stop="applyEdit">
					<input @keydown.esc="cancelEditing" v-model="editTitle" type="text" required>
					<input type="submit" value="" class="icon-confirm">
					<Actions>
						<ActionButton 
						icon="icon-close" 
						@click.stop.prevent="cancelEditing" />
					</Actions>
				</form>
			</div>
			<div class="board-list-avatars-cell" title="" v-show="!editing">
				<Avatar :user="board.owner.uid" :display-name="board.owner.displayname" class="board-list-avatar" />
				<Avatar v-for="user in limitedAcl"
					:key="user.id"
					:user="user.participant.uid"
					:display-name="user.participant.displayname"
					class="board-list-avatar" />
				<div v-if="board.acl.length > 5" v-tooltip="otherAcl" class="avatardiv popovermenu-wrapper board-list-avatar icon-more" />
			</div>
			<div class="board-list-actions-cell"  v-show="!editing" />
		</router-link>
		<div class="content" v-collapse-content v-show="collapseContent">
			<div style="align-items: center;">
			</div>
			<div  
				v-show="canDisplayBoardChildren"  
				v-for="board in childBoards" :key="board.id" 
				class="sub-board-list">
				<BoardItem :board="board" :boardLevel="nextPaddingLevel" />
			</div>
		</div>
	</v-collapse-wrapper>
</template>

<script>
import { Avatar,  ColorPicker, Actions, ActionButton } from '@nextcloud/vue'
import ClickOutside from 'vue-click-outside'

export default {
	name: 'BoardItem',
	components: {
		Avatar,
		ColorPicker,
		Actions,
		ActionButton,
	},
	directives: {
		ClickOutside,
	},
	props: {
		board: {
			type: Object,
			default: () => { return {} },
		},
		paddingIdentend: {
			default: 17
		},
		boardLevel : {
			default: 0
		},
	},
	data() {
		return  {
			collapseContent: false,
			childBoards: [],
			isFetching: false,
			bulletOpen: false,
			editing: false,
			editTitle: '',
			editColor: '',
		}
	},
	methods: {
		toggleCollapse(e) {

			this.collapseContent = !this.collapseContent
			this.bulletOpen = !this.bulletOpen

			// Only fetch if childBoards dont has items
			if( this.collapseContent && this.childBoards.length == 0 ){ 
				this.isFetching = true
				this.$store.dispatch('loadBoardsByParentId', {parentId: this.board.id})
					.then(
						(boards) => {
							this.childBoards = boards
						}
					)
					.finally(()=> this.isFetching = false )
			}
		},
		activeEditItem(e) {
			this.editTitle = this.board.title
			this.editColor = '#' + this.board.color
			this.editing = true
		},
		cancelEditing(e) {
			this.editTitle = this.board.title
			this.editColor = '#' + this.board.color
			this.editing = false
		},
		updateColor(newColor) {
			this.editColor = newColor
		},
		applyEdit(e) {
			this.editing = false
			if (this.editTitle || this.editColor) {
				this.loading = true
				const copy = JSON.parse(JSON.stringify(this.board))
				copy.title = this.editTitle
				copy.color = (typeof this.editColor.hex !== 'undefined' ? this.editColor.hex : this.editColor).substring(1)
				this.$store.dispatch('updateBoard', copy)
					.then(() => {
						this.loading = false
					})
			}
		},
	},
	computed: {
		routeTo: function() {
			return {
				name: 'board',
				params: { id: this.board.id },
			}
		},
		limitedAcl() {
			return [...this.board.acl].splice(0, 5)
		},
		otherAcl() {
			return [...this.board.acl].splice(6).map((item) => item.participant.displayname || item.participant).join(', ')
		},
		canDisplayBoardChildren() {
			return !this.isFetching && this.childBoards.length > 0 
		},
		canManage() {
			return this.board.permissions.PERMISSION_MANAGE
		},
		currentPaddingLevel() {
			return this.boardLevel * this.paddingIdentend
		},
		nextPaddingLevel() {
			return this.boardLevel + 1
		},
		getColor() {
			if (this.editColor !== '') {
				return this.editColor
			}
			return this.board.color
		},
	},
}
</script>

<style lang="scss" scoped>

	.board-list-bullet-cell {
		padding: 6px 15px;

		.board-list-bullet {
			border-radius: 50%;
			cursor: pointer;
			height: 32px;
			width: 32px;
		}
	}

	.board-list-title-cell {
		padding: 0 15px;
	}

	.sub-board-list {
		padding-left: 17px;
	}

	.bullet-caret {
		display: none;
	}

	.edit-button {
		display: none;
	}

	.bullet-area:hover {
		
		.bullet-color {
			display: none;
		}

		.bullet-caret {
			display: block; 
		}

		.edit-button {
			display: inline;
		}
	}

	.board-list-avatars-cell {
		display: flex;
		padding: 6px 15px;

		.board-list-avatar {
			border-radius: 50%;
			height: 32px;
			width: 32px;
			margin-left: 3px;
			&.icon-more {
				background-color:var(--color-background-dark);
			}
		}
	}

	.board-edit {
		margin-left: 44px;
		order: 1;
		display: flex;
		height: 44px;

		form {
			display: flex;
			flex-grow: 1;

			input[type="text"] {
				flex-grow: 1;
			}
		}
	}

	.app-navigation-entry-bullet-wrapper {
		width: 44px;
		height: 44px;
		.color0 {
			width: 30px !important;
			margin: 5px;
			margin-left: 7px;
			height: 30px;
			border-radius: 50%;
			background-size: 14px;
		}
	}
</style>
