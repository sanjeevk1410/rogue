import React from 'react';
import './paging-buttons.scss';

class PagingButtons extends React.Component {
  render() {
    const prev = this.props.prev;
    const next = this.props.next;

    return (
      <div className="container__block">
        <a href={prev} onClick={e => this.props.onPaginate(prev, e)} disabled={prev}>{prev ? "< previous" : null}</a>
        <div className="next-page">
          <a href={next} onClick={e => this.props.onPaginate(next, e)}>{next ? "next >" : null}</a>
        </div>
      </div>
    )
  }
}

export default PagingButtons;
