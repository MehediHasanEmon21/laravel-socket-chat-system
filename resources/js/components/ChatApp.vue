<template>
    <div class="container-chat clearfix">
            <div class="people-list" id="people-list">
      <div class="search">
        <input type="text" placeholder="search" />
        <i class="fa fa-search"></i>
      </div>
      <ul class="list">
     
        <li @click.prevent="selectUser(user.id)" class="clearfix" v-for="user in userList" :key="user.id">
        
          <div class="about">
            <div class="name">{{user.name}}</div>
            <div class="status">
              <div style="color: #fff" v-if="onlineUser(user.id) || online.id == user.id">
                <i class="fa fa-circle online" style="color: #fff"></i> online
              </div>
              <div style="color: #000" v-else>
                <i class="fa fa-circle online" style="color: #000"></i> offline
              </div>
            </div>
          </div>
        </li>
        
      
      </ul>
    </div>
    
    <div class="chat">
      <div class="chat-header clearfix" v-if="userMessage.user">
        <img src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/195612/chat_avatar_01_green.jpg" alt="avatar" />
        
        <div class="chat-about">
          <div class="chat-with" v-if="userMessage.user">{{userMessage.user.name}}</div>
        </div>
        <i class="fa fa-star"></i>
        <ul class="nav nav-tabs">
          
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">...</a>
            <div class="dropdown-menu">
              <a @click.prevent="deleteAllMessage" class="dropdown-item" href="#">Delete All Message</a>
            </div>
          </li>
          
        </ul>
      </div> <!-- end chat-header -->
      
      <div class="chat-history" v-chat-scroll>
        <ul>

          <li class="clearfix" v-for="message in userMessage.messages" :key="message.id">
            <div class="message-data align-right">
              <span class="message-data-time" >{{message.created_at}}</span> &nbsp; &nbsp;
              <span class="message-data-name" >{{message.user.name}}</span> <i class="fa fa-circle me"></i>
              <ul class="nav nav-tabs">
               
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">...</a>
                  <div class="dropdown-menu">
                    <a @click.prevent="deleteSingleMessage(message.id)" class="dropdown-item" href="#">Delete Message</a>
                  </div>
                </li>
               
              </ul>
              
            </div>
            <div :class="`message float-right ${userMessage.user.id == message.user.id ? 'other-message' : 'my-message'}`">
              {{message.message}}
            </div>
          </li>
          
          

          
        </ul>
        
      </div> <!-- end chat-history -->
      
      <div class="chat-message clearfix">
        <small style="color: green" v-if="typerName">{{ typerName }} is typing....</small>
        <textarea v-if="userMessage.user" @keydown.enter="sendMessage" @keyup.prevent="typeEvent(userMessage.user.id)" v-model="message" name="message-to-send" id="message-to-send" placeholder ="Type your message" rows="3"></textarea>
        <textarea v-else disabled name="message-to-send" id="message-to-send" placeholder ="Type your message" rows="3"></textarea>
                
        <i class="fa fa-file-o"></i> &nbsp;&nbsp;&nbsp;
        <i class="fa fa-file-image-o"></i>
        
        <button>Send</button>

      </div> <!-- end chat-message -->
      
    </div> <!-- end chat -->
        
  </div> <!-- end container -->
</template>

<script>
import _ from 'lodash';

export default {
  
   data(){

    return{
      message:'',
      typerName: '',
      onlineUsers: [],
      online: {}
    }

  },

  mounted(){
    
    this.$store.dispatch('userList');

    //listen meesage for single user
    Echo.private(`chat.${auth_user.id}`)
    .listen('MessageSend', (e) => {
        this.selectUser(e.message.from);
    });
    
    //Typing event when typing
    Echo.private('type')
    .listenForWhisper('typing', (e) => {
        if(this.userMessage.user){

          if(e.auth_user.id == this.userMessage.user.id && e.selectUserId == auth_user.id){
            this.typerName = e.auth_user.name;
          }
          setTimeout(()=>{
            this.typerName = '';
          },1000) 

        }
      });

    //show live users
    Echo.join(`liveuser`)
    .here((users) => {
        this.onlineUsers = users;
    })
    .joining((user) => {
        this.online = user;
    })
    .leaving((user) => {
        this.online = '';
    });
    
  },

  computed:{
    userList(){
      return  this.$store.getters.userList
    },
     userMessage(){
      return  this.$store.getters.userMessage
    },

  },

  methods:{
    selectUser(id){
      this.$store.dispatch('userMessage',id);
    },

    sendMessage(e){
      e.preventDefault();
      if(this.message != ''){
        axios.post('/send-message',{message: this.message, user_id: this.userMessage.user.id})
        .then((res)=> {
          this.selectUser(this.userMessage.user.id);
        })
        this.message = '';
      }
      
    },

    deleteAllMessage(){
      axios.get(`/delete-all-message/${this.userMessage.user.id}`)
      .then((res)=> {
        this.selectUser(this.userMessage.user.id);
      })
    },

    deleteSingleMessage(message_id){
      axios.get(`/delete-single-message/${message_id}`)
      .then((res)=> {
        this.selectUser(this.userMessage.user.id);
      })
    },

    typeEvent(selectUserId){
      Echo.private('type')
        .whisper('typing', {
            auth_user: auth_user,
            message: this.message,
            selectUserId: selectUserId
        });
    },

    onlineUser(userId){
      return _.find(this.onlineUsers,{'id':userId});
    }
  }
}
</script>

<style>
  .people-list ul{overflow-y: scroll!important}
</style>
