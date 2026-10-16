interface RenderScopesProps {
  header?: string;
  values: string[];
}

const RenderScopes = ({ header, values }: RenderScopesProps) => {
  return (
    <div>
      <p className={'font-medium mb-4'}>{header}</p>
      <ul>
        {values.map((value) => {
          return (
            <li className={'mb-4 last:mb-0 flex items-center gap-2'} key={value}>
              <div className={'bg-[#D9D9D9] size-2 shrink-0 rounded-full'} />
              {value}
            </li>
          );
        })}
      </ul>
    </div>
  );
};

export default RenderScopes;
