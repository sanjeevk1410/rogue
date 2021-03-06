/**
 * Wait until the DOM is ready.
 *
 * @param {Function} fn
 */
import { flatMap, keyBy } from 'lodash';

export function ready(fn) {
  if (document.readyState !== 'loading'){
    fn();
  } else {
    document.addEventListener('DOMContentLoaded', fn);
  }
}

export function calculateAge(date) {
	const birthdate = new Date(date);
	const today = Date.now();
	const age = Math.floor((today - birthdate) / 31536000000);

	return age;
};

export function getImageUrlFromProp(photoProp) {
	var photo_url;

  // Sometimes we get the url right on the post and sometimes it is nested under
  // media (in cases where it goes through the PostTransformer), so handle both cases
  // @TODO: make sure everything goes through a transformer so we don't need this
  if ('url' in photoProp) {
    photo_url = photoProp['url'];
  }
  else if ('media' in photoProp) {
    photo_url = photoProp['media']['original_image_url'];
  }


  if (photo_url == "default") {
    return "https://www.dosomething.org/sites/default/files/JenBugError.png";
  }
	else {
	  return photo_url;
	}
};

export function extractPostsFromSignups(signups) {
  const posts = keyBy(flatMap(signups, signup => {
    return signup.posts;
  }), 'id');

  return posts;
}

export function extractSignupsFromPosts(posts) {
  const signups = keyBy(flatMap(posts, post => {
    return post.signup.data;
  }), 'signup_id');

  return signups;
}

export function getEditedImageUrl(photoProp) {
  const edited_file_name = `edited_${photoProp.id}.jpeg`;
  var url_parts;

  // Sometimes we get the url right on the post and sometimes it is nested under
  // media (in cases where it goes through the PostTransformer), so handle both cases
  if ('url' in photoProp) {
    url_parts = photoProp['url'].split("/");
    url_parts.pop();
    url_parts.push(edited_file_name);

    return url_parts.join('/');
  }
  else if ('media' in photoProp) {
    return photoProp['media']['url'];
  }

  return null;
};

/**
 * Returns a readable display name.
 *
 * @param {String} firstName
 * @param {String} lastName
 */
export function displayName(firstName, lastName) {
  let displayName = firstName;

  if (lastName) {
    displayName = `${displayName} ${lastName}`;
  }

  return displayName;
}

/**
 * Returns a readable City and State string.
 *
 * @param {String} city
 * @param {String} state
 * @return {String|null} City and State string.
 */
export function displayCityState(city, state) {
  if (!city && !state) {
    return null;
  }

  return `${city ? city : ''}${city && state ? ', ' : ''}${state ? state : ''}`
}


/**
 * Returns a readable caption string.
 *
 * @param {Array} post
 * @return {String|null} caption string.
 */
export function displayCaption(post) {
  if (post['caption']) {
    return post['caption'];
  } else if (post.media) {
    return post.media['caption'];
  }

  return null;
}
