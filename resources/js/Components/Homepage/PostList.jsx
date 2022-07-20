import { formatTime } from '@/utils/jsHelper';
import { Inertia } from '@inertiajs/inertia';
import { Link } from '@inertiajs/inertia-react';
import { useEffect, useState } from 'react';
import NotificationAlert from '../Default/NotificationAlert';

export default function PostList(props) {
  const [newComment, setNewComment] = useState("")

  const [limiter, setLimiter] = useState(80)
  const [isValid, setIsValid] = useState(true)
  const [showNotif, setShowNotif] = useState(false)

  useEffect(() => {
    newComment.length >= 2 && newComment.length <= 80 ? setIsValid(true) : setIsValid(false)
  }, [newComment])

  useEffect(() => {
    props.notif ? setShowNotif(true) : setShowNotif(false)
    return () => props.notif
  }, [props])

  const handlerCommentInput = (value) => {
    setNewComment(value)
    const x = 80 - Number(value.length)
    if (x >= 0) {
      setLimiter(x)
    }
  }

  const handlerCommentSubmit = () => {
    const data = {
      post_id: props.posts.id,
      description: newComment
    }
    isValid && Inertia.post('/post/comment', data)
    setNewComment('')
    setLimiter(80)
    return () => isValid
  }

  const formValidateNotif = () => {
    return (
      <div className="alert alert-sm shadow-lg bg-base-300 mt-4">
        <div>
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" className="stroke-current flex-shrink-0 w-6 h-6"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          <span>{!limiter ? "Ngetiknya udah dulu ya, simpen buat komentar berikutnya 👍" : `Saat ini komentar kamu dibatasi ${limiter} karakter`}</span>
        </div>
      </div>
    )
  }

  return (
    <div className="card w-full md:w-2/3 bg-base-300 shadow-lg">
      {showNotif && <NotificationAlert message={props.notif} />}
      <div className="card-body p-6">
        <p className='text-xl break-all cursor-default'>{props.posts.description}</p>
        <div className="cursor-default text-xs">posted {formatTime(props.posts.created_at)}</div>
        <div className="card-actions flex flex-col justify-end items-end text-sm py-2">
          <div className='justify-center items-center flex flex-col'>
            <Link href={`/author/${props.posts.author}`} as="button" method="get" className="badge badge-inline p-4 max-w-xs cursor-pointer hover:translate-y-1 hover:transition-all">
              {props.posts.author}
            </Link>
          </div>
        </div>
        <div className="collapse rounded-md">
          <input type="checkbox" className="peer" />
          <div className="collapse-title bg-base-100 text-white text-white-content peer-checked:bg-white peer-checked:text-black text-md">
            🧾 <i>lihat semua komentar</i>
          </div>
          <div className="collapse-content mt-3 p-0 bg-base-300 text-white-content peer-checked:bg-base-300 peer-checked:text-black-content">
            {props.posts.comments.map((comment, i) => {
              return (
                <div className='mb-3 shadow-lg flex flex-col bg-white text-base-300 p-6' key={i}>
                  <p className='text-xl font-mono font-bold'>{comment.description}</p>
                  <Link href={`/author/${comment.commentartor}`} as="div" method="get" className='mt-4 text-xs text-left cursor-pointer  hover:cursor-pointer hover:translate-x-1 hover:transition-all'>comment {formatTime(comment.created_at)} by {comment.commentartor}</Link>
                </div>
              )
            })}
          </div>
        </div>
        <div className='flex flex-col justify-center items-center gap-4 py-2'>
          {props.users && formValidateNotif()}
          <input type="text" minLength={2} maxLength={80} value={newComment} className='input w-full h-42' placeholder={props.users == null ? "Login untuk mengisi komentar" : "Tulis komentar"} onChange={(e) => handlerCommentInput(e.target.value)} disabled={props.users == null ? true : false} />
          <button type="button" className='btn btn-outline font-bold btn-sm w-full' disabled={props.users == null || !isValid ? true : false} onClick={() => handlerCommentSubmit()}>Komentar</button>
        </div>
      </div>
    </div >
  )
}