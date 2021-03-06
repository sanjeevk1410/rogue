import React from 'react';
import { keyBy, map, sample, forEach, reject } from 'lodash';
import { RestApiClient } from '@dosomething/gateway';

import { extractPostsFromSignups } from '../../helpers';
import Post from '../Post';
import HistoryModal from '../HistoryModal';
import ModalContainer from '../ModalContainer';

class CampaignInbox extends React.Component {
  constructor(props) {
    super(props);

    const posts = extractPostsFromSignups(props.signups);

    this.state = {
      signups: keyBy(props.signups, 'id'),
      posts: posts,
      users: props.users,
      displayHistoryModal: false,
      historyModalId: null,
    };

    this.api = new RestApiClient;
    this.updatePost = this.updatePost.bind(this);
    this.updateTag = this.updateTag.bind(this);
    this.updateQuantity = this.updateQuantity.bind(this);
    this.showHistory = this.showHistory.bind(this);
    this.hideHistory = this.hideHistory.bind(this);
    this.deletePost = this.deletePost.bind(this);
  }

  // Open the history modal of the given post
  showHistory(postId, event) {
    event.preventDefault()

    this.setState({
      displayHistoryModal: true,
      historyModalId: postId,
    });
  }

  // Close the open history modal
  hideHistory(event) {
    if (event) {
      event.preventDefault();
    }

    this.setState({
      displayHistoryModal: false,
      historyModalId: null,
    });
  }

  // Updates a post status.
  updatePost(postId, fields) {
    fields.post_id = postId;

    let request = this.api.put('reviews', fields);

    request.then((result) => {
      this.setState((previousState) => {
        const newState = {...previousState};
        newState.posts[postId].status = fields.status;

        return newState;
      });
    });
  }

  // Tag a post.
  updateTag(postId, tag) {
    const fields = {
      post_id: postId,
      tag_name: tag,
    };

    let response = this.api.post('tags', fields);

    return response.then((result) => {
      this.setState((previousState) => {
        const newState = {...previousState};
        const user = newState.posts[postId].user;

        newState.posts[postId] = result['data'];

        return newState;
      });
    });
  }

  // Update a signups quanity.
  updateQuantity(signup, newQuantity) {
    // Fields to send to /posts
    const fields = {
      northstar_id: signup.northstar_id,
      campaign_id: signup.campaign_id,
      campaign_run_id: signup.campaign_run_id,
      quantity: newQuantity,
    };

    // Make API request to Rogue to update the quantity on the backend
    let request = this.api.post('posts', fields);

    request.then((result) => {
      // Update the state
      this.setState((previousState) => {
        const newState = {...previousState};

        newState.signups[signup.id].quantity = result.quantity;

        return newState;
      });
    });

    // Close the modal
    this.hideHistory();
  }

  // Delete a post.
  deletePost(postId, event) {
    event.preventDefault();
    const confirmed = confirm('🚨🔥🚨Are you sure you want to delete this?🚨🔥🚨');

    if (confirmed) {
      // Make API request to Rogue to update the quantity on the backend
      let response = this.api.delete('posts/'.concat(postId));

      response.then((result) => {
        // Update the state
        this.setState((previousState) => {
          var newState = {...previousState};

          // Remove the deleted post from the state
          delete(newState.posts[postId]);

          // Return the new state
          return newState;
        });
      });
    }
  }

  render() {
    const posts = this.state.posts;
    const users = this.state.users;
    const campaign = this.props.campaign;
    const signups = this.state.signups;

    const nothingHere = [
      'https://media.giphy.com/media/3og0IT9dAZyMz3lXNe/giphy.gif',
      'https://media.giphy.com/media/Lny6Rw04nsOOc/giphy.gif',
      'https://media.giphy.com/media/YdhvjTeL83pNS/giphy.gif',
      'https://media.giphy.com/media/26ufnwz3wDUli7GU0/giphy.gif',
      'https://media.giphy.com/media/lYHbL5QY52Kcw/giphy.gif',
    ];

    if (posts.length !== 0) {
      return (
        <div className="container">
          {
            map(posts, (post, key) =>
              <Post key={key}
                post={post}
                user={users[post.northstar_id]}
                signup={signups[post.signup_id]}
                campaign={campaign}
                onUpdate={this.updatePost}
                onTag={this.updateTag}
                deletePost={this.props.deletePost}
                showHistory={this.showHistory}
                showSiblings={true}
                showQuantity={true}
                allowHistory={true} />
            )
          }

          <ModalContainer>
            {this.state.displayHistoryModal ?
              <HistoryModal
                id={this.state.historyModalId}
                onUpdate={this.updateQuantity}
                onClose={e => this.hideHistory(e)}
                campaign={campaign}
                signup={this.state.signups[posts[this.state.historyModalId]['signup_id']]}
              />
            : null}
          </ModalContainer>
        </div>
      )
    } else {
      // @todo - make this into an actual component.
      return (
        <div className="container">
          <h2 className="-padded">No Posts to review!</h2>
          <div className="container">
            <img src={sample(nothingHere)} />
          </div>
        </div>
      )
    }
  }
}

export default CampaignInbox;
